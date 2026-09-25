# MCP client

The `StreamableHttpMcpClient` implements the HTTP MCP boundary. It
supports:

- MCP initialization and capability negotiation;
- JSON-RPC responses;
- SSE-wrapped JSON-RPC responses;
- session identifiers and protocol-version headers;
- paginated `tools/list` and `resources/list`;
- `tools/call` and `resources/read`;
- normalized tool, resource and result DTOs.

Basic usage:

``` php
use GuzzleHttp\Client;
use Madj2k\AiMcp\Client\StreamableHttpMcpClient;

$client = new StreamableHttpMcpClient(
    httpClient: new Client(['timeout' => 10]),
    endpoint: 'https://mcp.example.test/mcp',
);

$client->initialize();
$tools = $client->listTools();
$result = $client->callTool('list_records', ['limit' => 10]);
```

The client does not decide which tool a model should call. It only
performs MCP communication. That decision belongs to the AI Core tool
loop or another host application.
