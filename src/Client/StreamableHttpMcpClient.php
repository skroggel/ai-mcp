<?php
declare(strict_types=1);

/*
 * This file is part of madj2k\ai-mcp
 *
 * Copyright (C) 2026 Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Madj2k\AiMcp\Client;

use GuzzleHttp\ClientInterface;
use Madj2k\AiMcp\Authentication\McpAuthenticationInterface;
use Madj2k\AiMcp\DTO\ResourceDefinition;
use Madj2k\AiMcp\DTO\ToolDefinition;
use Madj2k\AiMcp\DTO\ToolResult;
use Madj2k\AiMcp\Exception\McpException;

/**
 * Class StreamableHttpMcpClient
 *
 * Communicates with an MCP server through its Streamable HTTP endpoint.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final class StreamableHttpMcpClient implements McpClientInterface
{
    private int $requestId = 0;

    private ?string $sessionId = null;

    /**
     * @param ClientInterface $httpClient HTTP client.
     * @param string $endpoint MCP endpoint.
     * @param string $protocolVersion MCP protocol version.
     * @param array<string, string> $headers Additional HTTP headers.
     * @param McpAuthenticationInterface|null $authentication Dynamic authentication provider.
     */
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $endpoint,
        private readonly string $protocolVersion = '2025-06-18',
        private readonly array $headers = [],
        private readonly ?McpAuthenticationInterface $authentication = null,
    ) {
        if (trim($this->endpoint) === '') {
            throw new McpException('The MCP endpoint must not be empty.', 1789001001);
        }
    }

    /**
     * @inheritDoc
     */
    public function initialize(): array
    {
        $response = $this->request('initialize', [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'madj2k-ai-mcp',
                'version' => '0.1.0',
            ],
        ]);

        $this->notify('notifications/initialized');

        return is_array($response['capabilities'] ?? null) ? $response['capabilities'] : [];
    }

    /**
     * @inheritDoc
     */
    public function listTools(): array
    {
        $entries = $this->listPaginated('tools/list', 'tools');
        $tools = [];

        foreach ($entries as $tool) {
            if (!is_array($tool) || !is_string($tool['name'] ?? null)) {
                continue;
            }

            $tools[] = new ToolDefinition(
                name: $tool['name'],
                description: is_string($tool['description'] ?? null) ? $tool['description'] : '',
                inputSchema: is_array($tool['inputSchema'] ?? null) ? $tool['inputSchema'] : [],
            );
        }

        return $tools;
    }

    /**
     * @inheritDoc
     */
    public function callTool(string $name, array $arguments = []): ToolResult
    {
        $name = trim($name);
        if ($name === '') {
            throw new McpException('The MCP tool name must not be empty.', 1789001002);
        }

        $result = $this->request('tools/call', [
            'name' => $name,
            'arguments' => $arguments,
        ]);

        return new ToolResult(
            content: is_array($result['content'] ?? null) ? $result['content'] : [],
            isError: (bool)($result['isError'] ?? false),
            structuredContent: is_array($result['structuredContent'] ?? null) ? $result['structuredContent'] : [],
        );
    }

    /**
     * @inheritDoc
     */
    public function listResources(): array
    {
        $entries = $this->listPaginated('resources/list', 'resources');
        $resources = [];

        foreach ($entries as $resource) {
            if (!is_array($resource) || !is_string($resource['uri'] ?? null)) {
                continue;
            }

            $resources[] = new ResourceDefinition(
                uri: $resource['uri'],
                name: is_string($resource['name'] ?? null) ? $resource['name'] : '',
                description: is_string($resource['description'] ?? null) ? $resource['description'] : '',
                mimeType: is_string($resource['mimeType'] ?? null) ? $resource['mimeType'] : null,
            );
        }

        return $resources;
    }

    /**
     * Collects all pages of a cursor-based MCP list method.
     *
     * @param string $method MCP list method.
     * @param string $key Result item key.
     * @return array<int, mixed> Collected entries.
     */
    private function listPaginated(string $method, string $key): array
    {
        $entries = [];
        $cursor = null;

        do {
            $result = $this->request($method, $cursor !== null ? ['cursor' => $cursor] : []);
            foreach ((array)($result[$key] ?? []) as $entry) {
                $entries[] = $entry;
            }
            $nextCursor = $result['nextCursor'] ?? null;
            $cursor = is_string($nextCursor) && $nextCursor !== '' ? $nextCursor : null;
        } while ($cursor !== null);

        return $entries;
    }

    /**
     * @inheritDoc
     */
    public function readResource(string $uri): array
    {
        $uri = trim($uri);
        if ($uri === '') {
            throw new McpException('The MCP resource URI must not be empty.', 1789001003);
        }

        $result = $this->request('resources/read', ['uri' => $uri]);

        return is_array($result['contents'] ?? null) ? $result['contents'] : [];
    }

    /**
     * Sends one JSON-RPC request and returns its result.
     *
     * @param string $method JSON-RPC method.
     * @param array<string, mixed> $params Method parameters.
     * @return array<string, mixed> JSON-RPC result.
     */
    private function request(string $method, array $params = []): array
    {
        $id = ++$this->requestId;
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
        ];
        if ($params !== []) {
            $payload['params'] = $params;
        }

        try {
            $response = $this->httpClient->request('POST', $this->endpoint, [
                'headers' => array_merge([
                    'Accept' => 'application/json, text/event-stream',
                    'Content-Type' => 'application/json',
                    'MCP-Protocol-Version' => $this->protocolVersion,
                ], $this->headers, $this->authentication?->getHeaders() ?? [], $this->sessionId !== null ? ['Mcp-Session-Id' => $this->sessionId] : []),
                'json' => $payload,
            ]);
        } catch (\Throwable $exception) {
            throw new McpException('The MCP request failed: ' . $exception->getMessage(), 1789001004, $exception);
        }

        $sessionId = $response->getHeaderLine('Mcp-Session-Id');
        if ($sessionId !== '') {
            $this->sessionId = $sessionId;
        }

        $body = (string)$response->getBody();
        $decoded = $this->decodeResponse($body, $response->getHeaderLine('Content-Type'));
        if (!is_array($decoded)) {
            throw new McpException('The MCP server returned invalid JSON.', 1789001005);
        }

        if (isset($decoded['error']) && is_array($decoded['error'])) {
            throw new McpException(
                (string)($decoded['error']['message'] ?? 'The MCP server returned an error.'),
                (int)($decoded['error']['code'] ?? 1789001006),
            );
        }

        if (!is_array($decoded['result'] ?? null)) {
            throw new McpException('The MCP response does not contain a result.', 1789001007);
        }

        return $decoded['result'];
    }

    /**
     * Decodes a JSON or server-sent event MCP response.
     *
     * Streamable HTTP permits either a direct JSON-RPC response or an SSE
     * stream containing one or more JSON-RPC message events.
     *
     * @param string $body Response body.
     * @param string $contentType Response content type.
     * @return mixed Decoded JSON-RPC message.
     */
    private function decodeResponse(string $body, string $contentType): mixed
    {
        if (!str_contains(strtolower($contentType), 'text/event-stream')) {
            return json_decode($body, true);
        }

        $dataLines = [];
        $lastMessage = null;
        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            if (str_starts_with($line, 'data:')) {
                $dataLines[] = ltrim(substr($line, 5));
                continue;
            }

            if ($line === '' && $dataLines !== []) {
                $message = json_decode(implode("\n", $dataLines), true);
                if (is_array($message)) {
                    $lastMessage = $message;
                }
                $dataLines = [];
            }
        }

        if ($dataLines !== []) {
            $message = json_decode(implode("\n", $dataLines), true);
            if (is_array($message)) {
                $lastMessage = $message;
            }
        }

        return $lastMessage;
    }

    /**
     * Sends a JSON-RPC notification.
     *
     * @param string $method JSON-RPC method.
     * @return void
     */
    private function notify(string $method): void
    {
        try {
            $this->httpClient->request('POST', $this->endpoint, [
                'headers' => array_merge([
                    'Accept' => 'application/json, text/event-stream',
                    'Content-Type' => 'application/json',
                    'MCP-Protocol-Version' => $this->protocolVersion,
                ], $this->headers, $this->authentication?->getHeaders() ?? [], $this->sessionId !== null ? ['Mcp-Session-Id' => $this->sessionId] : []),
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => $method,
                ],
            ]);
        } catch (\Throwable $exception) {
            throw new McpException('The MCP notification failed: ' . $exception->getMessage(), 1789001008, $exception);
        }
    }
}
