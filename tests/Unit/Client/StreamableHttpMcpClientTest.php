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

namespace Madj2k\AiMcp\Tests\Unit\Client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use Madj2k\AiMcp\Client\StreamableHttpMcpClient;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamableHttpMcpClientTest
 *
 * Tests the Streamable HTTP MCP client against deterministic responses.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp\Tests
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final class StreamableHttpMcpClientTest extends TestCase
{
    /**
     * Tests discovery, resource reads and tool calls.
     *
     * @return void
     */
    public function testClientMapsMcpResponses(): void
    {
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, ['Mcp-Session-Id' => 'test-session'], json_encode([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['capabilities' => ['tools' => ['listChanged' => false]]],
            ], JSON_THROW_ON_ERROR)),
            new Response(202),
            new Response(200, [], json_encode([
                'jsonrpc' => '2.0',
                'id' => 2,
                'result' => ['tools' => [['name' => 'list_entities', 'inputSchema' => ['type' => 'object']]]],
            ], JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode([
                'jsonrpc' => '2.0',
                'id' => 3,
                'result' => ['contents' => [['uri' => 'shop://schema', 'text' => '{}']]],
            ], JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode([
                'jsonrpc' => '2.0',
                'id' => 4,
                'result' => ['content' => [['type' => 'text', 'text' => 'ok']], 'structuredContent' => ['status' => 'ok']],
            ], JSON_THROW_ON_ERROR)),
        ]));

        $client = new StreamableHttpMcpClient(
            new Client(['handler' => $handler]),
            'http://mcp.test/mcp',
        );

        self::assertSame(['tools' => ['listChanged' => false]], $client->initialize());
        self::assertSame('list_entities', $client->listTools()[0]->name);
        self::assertSame('shop://schema', $client->readResource('shop://schema')[0]['uri']);
        self::assertSame('ok', $client->callTool('list_entities')->getText());
    }

    /**
     * Tests decoding an SSE-wrapped JSON-RPC response.
     *
     * @return void
     */
    public function testClientDecodesServerSentEventResponse(): void
    {
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'text/event-stream'], "event: message\ndata: {\"jsonrpc\":\"2.0\",\"id\":1,\"result\":{\"capabilities\":[]}}\n\n"),
            new Response(202),
        ]));

        $client = new StreamableHttpMcpClient(
            new Client(['handler' => $handler]),
            'http://mcp.test/mcp',
        );

        self::assertSame([], $client->initialize());
    }
}
