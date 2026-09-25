..  _ai-mcp-installation:

Installation
============

Requirements
------------

* PHP 8.2 or newer;
* JSON extension;
* Guzzle 7.8 or newer;
* ``madj2k/ai-core`` for the AI Core tool-provider adapter.

Install the package with Composer:

.. code-block:: bash

    composer require madj2k/ai-mcp

The package has no TYPO3 or Symfony dependency. TYPO3 connection records, backend configuration
and diagnostics are provided by ``madj2k/t3-ai-assistant``.
