..  _ai-mcp-test-server:

DDEV test server
================

The ``test-server`` directory contains a read-only mini-shop MCP server for local integration
tests. It exposes:

* customers;
* products;
* orders;
* order items.

Available tools are:

* ``list_entities``;
* ``describe_entity``;
* ``find_records``;
* ``order_totals_by_country``.

The server also exposes the ``shop://schema`` resource. It intentionally does not expose arbitrary
SQL execution.

The service is configured by the root project's ``.ddev/docker-compose.mcp.yaml``:

.. code-block:: bash

    ddev start
    ddev exec curl http://mcp-server:8080/health

The MCP endpoint is ``http://mcp-server:8080/mcp`` from the DDEV network.
