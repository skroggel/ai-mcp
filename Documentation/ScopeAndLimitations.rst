..  _ai-mcp-scope-limitations:

Scope and limitations
=====================

The current client focuses on HTTP-hosted MCP servers and read-oriented tool integration. It
supports tools and resources required by the current AI Assistant integration.

The following features are not currently implemented:

* STDIO transport;
* MCP prompt discovery and prompt execution;
* resource templates and subscriptions;
* OAuth Authorization Code and PKCE flows;
* MCP Sampling, Roots and Elicitation;
* persistent capability synchronization.

These features can be added without changing the normalized AI Core tool-provider contract.
