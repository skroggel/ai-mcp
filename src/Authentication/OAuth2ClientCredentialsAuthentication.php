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

namespace Madj2k\AiMcp\Authentication;

use GuzzleHttp\ClientInterface;
use Madj2k\AiMcp\Exception\McpAuthenticationException;

/**
 * Class OAuth2ClientCredentialsAuthentication
 *
 * Implements OAuth 2.0 client credentials with in-memory token caching.
 *
 * @author Steffen Kroggel <developer@steffenkroggel.de>
 * @copyright Steffen Kroggel <developer@steffenkroggel.de>, Maximilian Fäßler <maximilian@faesslerweb.de>
 * @package Madj2k\AiMcp
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 */
final class OAuth2ClientCredentialsAuthentication implements McpAuthenticationInterface
{
    private ?string $accessToken = null;

    private int $expiresAt = 0;

    /**
     * @param ClientInterface $httpClient HTTP client for the token endpoint.
     * @param string $tokenEndpoint OAuth token endpoint.
     * @param string $clientId OAuth client identifier.
     * @param string $clientSecret OAuth client secret.
     * @param string $scope Optional space-separated scope list.
     */
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $tokenEndpoint,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $scope = '',
    ) {
    }

    /** @inheritDoc */
    public function getHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->getAccessToken()];
    }

    /**
     * Returns a cached or newly acquired access token.
     *
     * @return string Access token.
     * @throws McpAuthenticationException If token acquisition fails.
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken !== null && time() < $this->expiresAt) {
            return $this->accessToken;
        }

        if (trim($this->tokenEndpoint) === '' || trim($this->clientId) === '' || trim($this->clientSecret) === '') {
            throw new McpAuthenticationException('OAuth token endpoint, client ID and client secret are required.', 1789003001);
        }

        try {
            $form = ['grant_type' => 'client_credentials'];
            if (trim($this->scope) !== '') {
                $form['scope'] = trim($this->scope);
            }

            $response = $this->httpClient->request('POST', $this->tokenEndpoint, [
                'auth' => [$this->clientId, $this->clientSecret],
                'form_params' => $form,
                'headers' => ['Accept' => 'application/json'],
                'http_errors' => false,
            ]);
            $payload = json_decode((string)$response->getBody(), true);
        } catch (\Throwable $exception) {
            throw new McpAuthenticationException('OAuth token request failed: ' . $exception->getMessage(), 1789003002, $exception);
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300 || !is_array($payload)) {
            throw new McpAuthenticationException('OAuth token endpoint returned an invalid response.', 1789003003);
        }

        $token = trim((string)($payload['access_token'] ?? ''));
        if ($token === '') {
            throw new McpAuthenticationException('OAuth token endpoint did not return an access token.', 1789003004);
        }

        $this->accessToken = $token;
        $this->expiresAt = time() + max(1, (int)($payload['expires_in'] ?? 300) - 60);

        return $token;
    }
}
