..  _ai-mcp-authentication:

Authentication
==============

Bearer authentication
---------------------

Static bearer authentication can be passed as an HTTP header. The TYPO3 integration stores the
token on the MCP connection and adds the ``Authorization: Bearer`` header at runtime.

OAuth 2.0 Client Credentials
----------------------------

The package supports server-to-server OAuth 2.0 Client Credentials through
``OAuth2ClientCredentialsAuthentication``:

.. code-block:: php

    use GuzzleHttp\Client;
    use Madj2k\AiMcp\Authentication\OAuth2ClientCredentialsAuthentication;
    use Madj2k\AiMcp\Client\StreamableHttpMcpClient;

    $httpClient = new Client(['timeout' => 10]);
    $authentication = new OAuth2ClientCredentialsAuthentication(
        httpClient: $httpClient,
        tokenEndpoint: 'https://auth.example.test/oauth/token',
        clientId: 'mcp-client',
        clientSecret: $secret,
        scope: 'mcp.read',
    );

    $client = new StreamableHttpMcpClient(
        httpClient: $httpClient,
        endpoint: 'https://mcp.example.test/mcp',
        authentication: $authentication,
    );

Access tokens are cached in memory and renewed before expiry. Client secrets and access tokens must
not be written to logs.

Authorization Code and interactive PKCE flows are not included. They require application-specific
user interaction and callback handling.
