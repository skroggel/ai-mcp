# Madj2k AI MCP

Framework-independent MCP client for Madj2k AI integrations.

See [Documentation](documentation/Index.rst) for installation, authentication, AI Core
integration, the DDEV test server and current protocol coverage.

## Render the documentation

The documentation uses the framework-independent phpDocumentor Guides format. Render it from the
project root with Docker. The renderer uses an isolated Composer sandbox and does not modify the
project's Composer files:

```bash
docker run --rm --pull always -v "$(pwd)":/project composer:2 sh -lc '
  rm -rf /tmp/phpdocumentor-guides &&
  mkdir -p /tmp/phpdocumentor-guides &&
  cd /tmp/phpdocumentor-guides &&
  composer require --no-interaction phpdocumentor/guides-cli:^1.9 >/dev/null &&
  /tmp/phpdocumentor-guides/vendor/bin/guides \
    /project/documentation \
    --output=/project/documentation-GENERATED-temp &&
  php /project/tools/post-process-docs.php \
    /project/documentation-GENERATED-temp
'
```
