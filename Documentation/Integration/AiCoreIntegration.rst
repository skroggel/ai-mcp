..  _ai-mcp-ai-core-integration:

AI Core integration
===================

``McpToolProvider`` adapts MCP tools to the AI Core contracts:

.. code-block:: text

    MCP tool
        -> ToolDefinition
        -> ToolCall
        -> ToolResult

The provider implements ``ToolProviderInterface`` and can be registered in the AI Core
``ToolRegistry``. It supports:

* tool allowlists;
* qualified tool names for multiple MCP servers;
* per-provider round limits;
* structured MCP results.

The TYPO3 integration creates one provider per MCP connection assigned to an assistant. Pipeline
steps can override the assistant's MCP selection. Only the effective connections and their allowed
tools are exposed to the model.

The model can select a tool within that scope, but tool schemas do not replace server-side
authorization or argument validation.
