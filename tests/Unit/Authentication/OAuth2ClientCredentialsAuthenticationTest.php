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

namespace Madj2k\AiMcp\Tests\Unit\Authentication;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Madj2k\AiMcp\Authentication\OAuth2ClientCredentialsAuthentication;
use PHPUnit\Framework\TestCase;

/**
 * Class OAuth2ClientCredentialsAuthenticationTest
 *
 * Tests OAuth access-token acquisition and caching.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp\Tests
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final class OAuth2ClientCredentialsAuthenticationTest extends TestCase
{
    /**
     * Tests that the token endpoint is called once while the token is valid.
     *
     * @return void
     */
    public function testCachesAccessToken(): void
    {
        $handler = new MockHandler([
            new Response(200, [], '{"access_token":"token-123","expires_in":300}'),
        ]);
        $authentication = new OAuth2ClientCredentialsAuthentication(
            new Client(['handler' => $handler]),
            'https://oauth.test/token',
            'client-id',
            'client-secret',
            'mcp.read',
        );

        self::assertSame(['Authorization' => 'Bearer token-123'], $authentication->getHeaders());
        self::assertSame(['Authorization' => 'Bearer token-123'], $authentication->getHeaders());
        self::assertCount(0, $handler);
    }
}
