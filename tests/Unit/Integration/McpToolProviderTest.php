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

namespace Madj2k\AiMcp\Tests\Unit\Integration;

use Madj2k\AiCore\Assistant\Tool\DTO\ToolCall;
use Madj2k\AiMcp\Client\McpClientInterface;
use Madj2k\AiMcp\DTO\ResourceDefinition;
use Madj2k\AiMcp\DTO\ToolDefinition;
use Madj2k\AiMcp\DTO\ToolResult;
use Madj2k\AiMcp\Integration\McpToolProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class McpToolProviderTest
 *
 * Tests the mapping between MCP tools and ai-core tools.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp\Tests
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final class McpToolProviderTest extends TestCase
{
    /**
     * Tests tool discovery and execution mapping.
     *
     * @return void
     */
    public function testMapsMcpToolToCoreTool(): void
    {
        $client = new class implements McpClientInterface {
            public int $initializeCount = 0;

            public function initialize(): array
            {
                $this->initializeCount++;
                return [];
            }

            public function listTools(): array
            {
                return [new ToolDefinition('list_entities', 'Lists entities', ['type' => 'object'])];
            }

            public function callTool(string $name, array $arguments = []): ToolResult
            {
                return new ToolResult(
                    [['type' => 'text', 'text' => 'ok']],
                    structuredContent: ['name' => $name, 'arguments' => $arguments],
                );
            }

            public function listResources(): array
            {
                return [new ResourceDefinition('shop://schema')];
            }

            public function readResource(string $uri): array
            {
                return [];
            }
        };

        $provider = new McpToolProvider($client);
        self::assertSame('list_entities', $provider->getTools()[0]->name);
        self::assertSame('{"name":"list_entities","arguments":{"country":"US"}}', $provider->call(new ToolCall('1', 'list_entities', ['country' => 'US']))->content);
        self::assertSame(1, $client->initializeCount);

        $qualifiedProvider = new McpToolProvider($client, 'shop', ['list_entities'], 'mcp.shop');
        self::assertSame('mcp.shop.list_entities', $qualifiedProvider->getTools()[0]->name);

        $limitedProvider = new McpToolProvider($client, 'limited', [], '', 1);
        self::assertFalse($limitedProvider->call(new ToolCall('1', 'list_entities'))->isError);
        self::assertTrue($limitedProvider->call(new ToolCall('2', 'list_entities'))->isError);
    }
}
