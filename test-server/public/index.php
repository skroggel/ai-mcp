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

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR);
    return;
}

if ($path !== '/mcp' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(404);
    return;
}

try {
    $request = json_decode((string)file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $result = handleRequest(is_array($request) ? $request : []);

    header('Content-Type: application/json');
    header('Mcp-Session-Id: ddev-mini-shop-session');
    if (!array_key_exists('id', $request)) {
        http_response_code(202);
        return;
    }

    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => $request['id'],
        'result' => $result,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => $request['id'] ?? null,
        'error' => [
            'code' => -32603,
            'message' => $exception->getMessage(),
        ],
    ], JSON_THROW_ON_ERROR);
}

/**
 * Handles one JSON-RPC request.
 *
 * @param array<string, mixed> $request JSON-RPC request.
 * @return array<string, mixed> JSON-RPC result.
 */
function handleRequest(array $request): array
{
    $method = (string)($request['method'] ?? '');
    $params = is_array($request['params'] ?? null) ? $request['params'] : [];

    return match ($method) {
        'initialize' => [
            'protocolVersion' => (string)($params['protocolVersion'] ?? '2025-06-18'),
            'capabilities' => [
                'tools' => ['listChanged' => false],
                'resources' => ['subscribe' => false, 'listChanged' => false],
            ],
            'serverInfo' => ['name' => 'madj2k-mini-shop-mcp', 'version' => '0.1.0'],
        ],
        'notifications/initialized' => [],
        'tools/list' => ['tools' => toolDefinitions()],
        'resources/list' => ['resources' => resourceDefinitions()],
        'resources/read' => ['contents' => readResource((string)($params['uri'] ?? ''))],
        'tools/call' => callTool(
            (string)($params['name'] ?? ''),
            is_array($params['arguments'] ?? null) ? $params['arguments'] : [],
        ),
        default => throw new RuntimeException('Unsupported MCP method: ' . $method, -32601),
    };
}

/**
 * Returns the test server tool definitions.
 *
 * @return array<int, array<string, mixed>> Tool definitions.
 */
function toolDefinitions(): array
{
    return [
        [
            'name' => 'list_entities',
            'description' => 'Lists the available read-only shop entities.',
            'inputSchema' => ['type' => 'object', 'properties' => [], 'additionalProperties' => false],
        ],
        [
            'name' => 'describe_entity',
            'description' => 'Returns the columns and relations of one shop entity.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => ['entity' => ['type' => 'string']],
                'required' => ['entity'],
                'additionalProperties' => false,
            ],
        ],
        [
            'name' => 'find_records',
            'description' => 'Finds read-only records in one shop entity using validated filters.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'entity' => ['type' => 'string'],
                    'fields' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'filters' => ['type' => 'object', 'additionalProperties' => ['type' => ['string', 'number', 'boolean', 'null']]],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                ],
                'required' => ['entity'],
                'additionalProperties' => false,
            ],
        ],
        [
            'name' => 'order_totals_by_country',
            'description' => 'Aggregates order totals by customer country.',
            'inputSchema' => ['type' => 'object', 'properties' => [], 'additionalProperties' => false],
        ],
    ];
}

/**
 * Returns the test server resource definitions.
 *
 * @return array<int, array<string, mixed>> Resource definitions.
 */
function resourceDefinitions(): array
{
    return [
        [
            'uri' => 'shop://schema',
            'name' => 'Mini-shop schema',
            'description' => 'Schema and relationships of the read-only mini-shop database.',
            'mimeType' => 'application/json',
        ],
    ];
}

/**
 * Reads one test server resource.
 *
 * @param string $uri Resource URI.
 * @return array<int, array<string, mixed>> Resource contents.
 */
function readResource(string $uri): array
{
    if ($uri !== 'shop://schema') {
        throw new InvalidArgumentException('Unknown resource URI.', -32602);
    }

    return [[
        'uri' => $uri,
        'mimeType' => 'application/json',
        'text' => json_encode(schema(), JSON_THROW_ON_ERROR),
    ]];
}

/**
 * Executes one structured, read-only tool operation.
 *
 * @param string $name Tool name.
 * @param array<string, mixed> $arguments Tool arguments.
 * @return array<string, mixed> Tool result.
 */
function callTool(string $name, array $arguments): array
{
    try {
        $value = match ($name) {
            'list_entities' => array_keys(schema()['entities']),
            'describe_entity' => describeEntity((string)($arguments['entity'] ?? '')),
            'find_records' => findRecords($arguments),
            'order_totals_by_country' => orderTotalsByCountry(),
            default => throw new InvalidArgumentException('Unknown tool.', -32602),
        };

        return [
            'content' => [['type' => 'text', 'text' => json_encode($value, JSON_THROW_ON_ERROR)]],
            'structuredContent' => is_array($value) ? $value : ['value' => $value],
        ];
    } catch (Throwable $exception) {
        return [
            'isError' => true,
            'content' => [['type' => 'text', 'text' => $exception->getMessage()]],
        ];
    }
}

/**
 * Returns one entity description.
 *
 * @param string $entity Entity name.
 * @return array<string, mixed> Entity description.
 */
function describeEntity(string $entity): array
{
    $definition = schema()['entities'][$entity] ?? null;
    if (!is_array($definition)) {
        throw new InvalidArgumentException('Unknown entity.', -32602);
    }

    return ['name' => $entity] + $definition;
}

/**
 * Finds records using an allowlisted entity and columns.
 *
 * @param array<string, mixed> $arguments Tool arguments.
 * @return array<int, array<string, mixed>> Records.
 */
function findRecords(array $arguments): array
{
    $entity = (string)($arguments['entity'] ?? '');
    $definition = schema()['entities'][$entity] ?? null;
    if (!is_array($definition)) {
        throw new InvalidArgumentException('Unknown entity.', -32602);
    }

    $columns = $definition['columns'];
    $fields = is_array($arguments['fields'] ?? null) && $arguments['fields'] !== []
        ? $arguments['fields']
        : array_keys($columns);
    foreach ($fields as $field) {
        if (!is_string($field) || !array_key_exists($field, $columns)) {
            throw new InvalidArgumentException('Unknown entity field.', -32602);
        }
    }

    $filters = is_array($arguments['filters'] ?? null) ? $arguments['filters'] : [];
    $where = [];
    $values = [];
    foreach ($filters as $field => $value) {
        if (!is_string($field) || !array_key_exists($field, $columns)) {
            throw new InvalidArgumentException('Unknown filter field.', -32602);
        }
        $where[] = sprintf('`%s` = ?', $field);
        $values[] = $value;
    }

    $limit = min(100, max(1, (int)($arguments['limit'] ?? 25)));
    $sql = sprintf('SELECT `%s` FROM `%s`%s ORDER BY `id` DESC LIMIT %d', implode('`, `', $fields), $definition['table'], $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '', $limit);
    $statement = database()->prepare($sql);
    $statement->execute($values);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Aggregates order totals by country.
 *
 * @return array<int, array<string, mixed>> Aggregated totals.
 */
function orderTotalsByCountry(): array
{
    $statement = database()->query('SELECT c.country, COUNT(o.id) AS order_count, SUM(o.total_amount) AS total_amount FROM orders o INNER JOIN customers c ON c.id = o.customer_id GROUP BY c.country ORDER BY total_amount DESC');

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Returns the database connection, initializing the fixture on first use.
 *
 * @return PDO Database connection.
 */
function database(): PDO
{
    static $database;
    if ($database instanceof PDO) {
        return $database;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: 'db', getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE') ?: 'db');
    $lastException = null;
    for ($attempt = 0; $attempt < 15; $attempt++) {
        try {
            $database = new PDO($dsn, getenv('DB_USER') ?: 'db', getenv('DB_PASSWORD') ?: 'db', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            initializeDatabase($database);
            return $database;
        } catch (Throwable $exception) {
            $lastException = $exception;
            usleep(500000);
        }
    }

    throw new RuntimeException('Could not connect to the DDEV database: ' . ($lastException?->getMessage() ?? 'unknown error'));
}

/**
 * Creates and seeds the fixture database idempotently.
 *
 * @param PDO $database Database connection.
 * @return void
 */
function initializeDatabase(PDO $database): void
{
    $database->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $database->exec(file_get_contents(__DIR__ . '/../database/seed.sql'));
}

/**
 * Returns the public shop schema.
 *
 * @return array<string, mixed> Shop schema.
 */
function schema(): array
{
    return [
        'entities' => [
            'customers' => ['table' => 'customers', 'columns' => ['id' => 'integer', 'name' => 'string', 'email' => 'string', 'country' => 'string', 'created_at' => 'datetime']],
            'products' => ['table' => 'products', 'columns' => ['id' => 'integer', 'sku' => 'string', 'name' => 'string', 'category' => 'string', 'price' => 'decimal', 'active' => 'boolean']],
            'orders' => ['table' => 'orders', 'columns' => ['id' => 'integer', 'customer_id' => 'integer', 'status' => 'string', 'ordered_at' => 'datetime', 'total_amount' => 'decimal']],
            'order_items' => ['table' => 'order_items', 'columns' => ['id' => 'integer', 'order_id' => 'integer', 'product_id' => 'integer', 'quantity' => 'integer', 'unit_price' => 'decimal']],
        ],
        'relations' => [
            ['from' => 'orders.customer_id', 'to' => 'customers.id'],
            ['from' => 'order_items.order_id', 'to' => 'orders.id'],
            ['from' => 'order_items.product_id', 'to' => 'products.id'],
        ],
    ];
}
