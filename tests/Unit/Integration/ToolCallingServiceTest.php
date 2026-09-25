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

use Madj2k\AiCore\Assistant\Configuration\PipelineStepConfigurationInterface;
use Madj2k\AiCore\Assistant\Tool\DTO\ToolCall;
use Madj2k\AiCore\Assistant\Tool\DTO\ToolDefinition;
use Madj2k\AiCore\Assistant\Tool\Provider\ToolProviderInterface;
use Madj2k\AiCore\Assistant\Tool\DTO\ToolResult;
use Madj2k\AiCore\Assistant\Tool\Execution\ToolCallingService;
use Madj2k\AiCore\Connection\Ai\AiConnectorInterface;
use Madj2k\AiCore\Connection\Ai\DTO\AiRequest;
use Madj2k\AiCore\Connection\Ai\DTO\AiResponse;
use Madj2k\AiCore\Connection\Ai\DTO\EmbeddingRequest;
use Madj2k\AiCore\Connection\Ai\DTO\EmbeddingResponse;
use Madj2k\AiCore\Connection\Configuration\AiConnectionConfiguration;
use Madj2k\AiCore\Connection\Resolver\AiConnectorResolver;
use PHPUnit\Framework\TestCase;

/**
 * Class ToolCallingServiceTest
 *
 * Tests a complete model/tool/model interaction loop.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp\Tests
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final class ToolCallingServiceTest extends TestCase
{
    /**
     * Tests that a tool call is executed before the final answer.
     *
     * @return void
     */
    public function testRunsToolLoop(): void
    {
        $connector = new class implements AiConnectorInterface {
            public int $calls = 0;

            public function getIdentifier(): string { return 'fake'; }

            public function chat($connection, AiRequest $request): AiResponse
            {
                $this->calls++;
                if ($this->calls === 1) {
                    return new AiResponse('', [
                        'choices' => [[
                            'message' => [
                                'tool_calls' => [[
                                    'id' => 'call_1',
                                    'function' => [
                                        'name' => 'list_entities',
                                        'arguments' => '{}',
                                    ],
                                ]],
                            ],
                        ]],
                    ]);
                }

                return new AiResponse('There are four entities.');
            }

            public function streamChat($connection, AiRequest $request, callable $onData): void {}

            public function embed($connection, EmbeddingRequest $request): EmbeddingResponse
            {
                return new EmbeddingResponse([], 'fake');
            }

            public function embedBatch($connection, array $requests): array { return []; }
        };

        $provider = new class implements ToolProviderInterface {
            public function getIdentifier(): string { return 'test'; }

            public function getTools(): array
            {
                return [new ToolDefinition('list_entities')];
            }

            public function call(ToolCall $call): ToolResult
            {
                return new ToolResult('["customers","orders"]');
            }
        };

        $step = $this->createStub(PipelineStepConfigurationInterface::class);
        $step->method('getModel')->willReturn('test-model');
        $step->method('getTemperature')->willReturn(0.0);
        $step->method('getMaxTokens')->willReturn(100);

        $service = new ToolCallingService(
            new AiConnectorResolver([$connector]),
            new \Madj2k\AiCore\Assistant\Tool\Registry\ToolRegistry([$provider]),
        );

        $answer = $service->run(
            null,
            [new \Madj2k\AiCore\Connection\Ai\DTO\AiMessage('user', 'List entities.')],
            $step,
            new AiConnectionConfiguration('test-key', defaultModel: 'test-model', connectorIdentifier: 'fake'),
        );

        self::assertSame('There are four entities.', $answer);
        self::assertSame(2, $connector->calls);
    }
}
