# Madj2k AI MCP

Framework-independent MCP client for Madj2k AI integrations.

The package currently provides a Streamable HTTP MCP client with support for:

- MCP initialization and capability negotiation
- tool discovery and calls
- resource discovery and reads
- normalized tool and resource DTOs

The `test-server` directory contains a read-only mini-shop MCP server for local DDEV integration tests. It exposes customers, products, orders and order items through structured tools. It intentionally does not expose arbitrary SQL execution.

## Test server

The test server is configured through the root project's `.ddev/docker-compose.mcp.yaml`. Start it with:

```bash
ddev start
ddev exec curl http://mcp-server:8080/health
```

The MCP endpoint is `http://mcp-server:8080/mcp` from the DDEV network.
