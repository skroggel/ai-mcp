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

namespace Madj2k\AiMcp\Integration;

use Madj2k\AiCore\Assistant\Tool\DTO\ToolCall;
use Madj2k\AiCore\Assistant\Tool\DTO\ToolDefinition;
use Madj2k\AiCore\Assistant\Tool\Provider\ToolProviderInterface;
use Madj2k\AiCore\Assistant\Tool\DTO\ToolResult as CoreToolResult;
use Madj2k\AiMcp\Client\McpClientInterface;

/**
 * Class McpToolProvider
 *
 * Exposes tools from one MCP server to the ai-core tool loop.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final class McpToolProvider implements ToolProviderInterface
{
    private bool $initialized = false;

    /**
     * @param McpClientInterface $client MCP client.
     * @param string $identifier Provider identifier.
     * @param array<int, string> $allowedTools Optional tool allowlist.
     * @param string $toolPrefix Optional qualified tool-name prefix.
     * @param int $maxRounds Maximum calls allowed through this provider per request.
     */
    public function __construct(
        private McpClientInterface $client,
        private string $identifier = 'mcp',
        private array $allowedTools = [],
        private string $toolPrefix = '',
        private int $maxRounds = 10,
    ) {
        $this->maxRounds = max(1, $this->maxRounds);
    }

    private int $toolCallCount = 0;

    /** @inheritDoc */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /** @inheritDoc */
    public function getTools(): array
    {
        $this->initialize();

        $tools = array_map(
            fn (\Madj2k\AiMcp\DTO\ToolDefinition $tool): ToolDefinition => new ToolDefinition(
                name: $this->qualifyName($tool->name),
                description: $tool->description,
                inputSchema: $tool->inputSchema,
            ),
            $this->client->listTools(),
        );

        if ($this->allowedTools === []) {
            return $tools;
        }

        return array_values(array_filter(
            $tools,
            fn (ToolDefinition $tool): bool => in_array($this->unqualifyName($tool->name), $this->allowedTools, true),
        ));
    }

    /** @inheritDoc */
    public function call(ToolCall $call): CoreToolResult
    {
        $this->initialize();

        if ($this->allowedTools !== [] && !in_array($this->unqualifyName($call->name), $this->allowedTools, true)) {
            return new CoreToolResult('The requested MCP tool is not allowed.', true);
        }

        if ($this->toolCallCount >= $this->maxRounds) {
            return new CoreToolResult('The MCP connection round limit was exceeded.', true);
        }

        $this->toolCallCount++;

        $result = $this->client->callTool($this->unqualifyName($call->name), $call->arguments);
        $content = $result->structuredContent !== []
            ? json_encode($result->structuredContent, JSON_THROW_ON_ERROR)
            : $result->getText();

        return new CoreToolResult($content, $result->isError);
    }

    /**
     * Initializes the MCP session once.
     *
     * @return void
     */
    private function initialize(): void
    {
        if (!$this->initialized) {
            $this->client->initialize();
            $this->initialized = true;
        }
    }

    /**
     * Qualifies a server tool name for multi-server model usage.
     *
     * @param string $name Server tool name.
     * @return string Qualified tool name.
     */
    private function qualifyName(string $name): string
    {
        return $this->toolPrefix !== '' ? $this->toolPrefix . '.' . $name : $name;
    }

    /**
     * Removes the provider prefix before calling the MCP server.
     *
     * @param string $name Qualified tool name.
     * @return string Server tool name.
     */
    private function unqualifyName(string $name): string
    {
        $prefix = $this->toolPrefix . '.';

        return $this->toolPrefix !== '' && str_starts_with($name, $prefix)
            ? substr($name, strlen($prefix))
            : $name;
    }
}
