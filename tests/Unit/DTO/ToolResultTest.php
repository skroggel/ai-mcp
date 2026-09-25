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

namespace Madj2k\AiMcp\Tests\Unit\DTO;

use Madj2k\AiMcp\DTO\ToolResult;
use PHPUnit\Framework\TestCase;

/**
 * Class ToolResultTest
 *
 * Tests normalized MCP tool results.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp\Tests
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final class ToolResultTest extends TestCase
{
    /**
     * Tests textual content extraction.
     *
     * @return void
     */
    public function testGetTextReturnsTextContentOnly(): void
    {
        $result = new ToolResult([
            ['type' => 'text', 'text' => 'First'],
            ['type' => 'image', 'data' => 'ignored'],
            ['type' => 'text', 'text' => 'Second'],
        ]);

        self::assertSame("First\nSecond", $result->getText());
    }
}
