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

use Madj2k\AiMcp\DTO\ResourceDefinition;
use Madj2k\AiMcp\DTO\ToolDefinition;
use Madj2k\AiMcp\DTO\ToolResult;

/**
 * Interface McpClientInterface
 *
 * Defines the framework-independent MCP client contract.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
interface McpClientInterface
{
    /**
     * Initializes the MCP session.
     *
     * @return array<string, mixed> Negotiated server capabilities.
     */
    public function initialize(): array;

    /**
     * Lists tools exposed by the server.
     *
     * @return array<int, ToolDefinition> Tool definitions.
     */
    public function listTools(): array;

    /**
     * Calls one server tool.
     *
     * @param string $name Tool name.
     * @param array<string, mixed> $arguments Tool arguments.
     * @return ToolResult Tool result.
     */
    public function callTool(string $name, array $arguments = []): ToolResult;

    /**
     * Lists resources exposed by the server.
     *
     * @return array<int, ResourceDefinition> Resource definitions.
     */
    public function listResources(): array;

    /**
     * Reads one server resource.
     *
     * @param string $uri Resource URI.
     * @return array<int, array<string, mixed>> Resource contents.
     */
    public function readResource(string $uri): array;
}
