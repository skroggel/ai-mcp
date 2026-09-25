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

namespace Madj2k\AiMcp\DTO;

/**
 * Class ToolResult
 *
 * Contains normalized MCP tool output.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final readonly class ToolResult
{
    /**
     * Constructor.
     *
     * @param array<int, array<string, mixed>> $content MCP content blocks.
     * @param bool $isError Whether the server marked the result as an error.
     * @param array<string, mixed> $structuredContent Structured tool output.
     */
    public function __construct(
        public array $content = [],
        public bool $isError = false,
        public array $structuredContent = [],
    ) {
    }

    /**
     * Returns textual content blocks joined with a newline.
     *
     * @return string Textual result content.
     */
    public function getText(): string
    {
        $text = [];
        foreach ($this->content as $content) {
            if (($content['type'] ?? null) === 'text' && is_string($content['text'] ?? null)) {
                $text[] = $content['text'];
            }
        }

        return implode("\n", $text);
    }
}
