#!/usr/bin/env php
<?php

/**
 * Import docs/test-cases.csv into a Notion database.
 *
 * Setup:
 * 1. Share your Notion page/database with the "timesheet" integration
 *    (page ⋯ → Connections → timesheet)
 * 2. Export token: export NOTION_TOKEN="ntn_..."
 * 3. Export database ID from URL (32-char hex, with or without dashes)
 *    export NOTION_DATABASE_ID="38bdf2130a1f8068967ffc1b2bec5a2f"
 * 4. Run: php docs/import-to-notion.php
 */

declare(strict_types=1);

const NOTION_VERSION = '2022-06-28';
const CSV_PATH = __DIR__ . '/test-cases.csv';

$token = getenv('NOTION_TOKEN') ?: ($argv[1] ?? null);
$databaseId = getenv('NOTION_DATABASE_ID') ?: ($argv[2] ?? null);

if (! $token || ! $databaseId) {
    fwrite(STDERR, "Usage: NOTION_TOKEN=ntn_... NOTION_DATABASE_ID=... php docs/import-to-notion.php\n");
    fwrite(STDERR, "   or: php docs/import-to-notion.php <token> <database_id>\n");
    exit(1);
}

$databaseId = formatUuid($databaseId);

if (! is_file(CSV_PATH)) {
    fwrite(STDERR, "Missing " . CSV_PATH . " — run: php docs/generate-test-catalog.php\n");
    exit(1);
}

$schema = notionRequest('GET', "/databases/{$databaseId}", $token);

if (($schema['object'] ?? '') === 'error') {
    fwrite(STDERR, "Notion error: {$schema['message']}\n");
    fwrite(STDERR, "Share the database with your integration: page ⋯ → Connections → timesheet\n");
    exit(1);
}

$properties = $schema['properties'] ?? [];
$titleProp = findProperty($properties, ['title', 'name', 'Title', 'Name'], 'title');

if ($titleProp === null) {
    fwrite(STDERR, "Could not find a title property on this database.\n");
    exit(1);
}

$rows = readCsv(CSV_PATH);
$created = 0;
$skipped = 0;

foreach ($rows as $row) {
    $testId = $row['Test Case ID'] ?? '';

    if ($testId !== '' && notionPageExists($databaseId, $token, $titleProp, $testId)) {
        $skipped++;
        continue;
    }

    $payload = buildPagePayload($databaseId, $titleProp, $properties, $row);
    $result = notionRequest('POST', '/pages', $token, $payload);

    if (($result['object'] ?? '') === 'error') {
        fwrite(STDERR, "Failed {$testId}: {$result['message']}\n");
        exit(1);
    }

    $created++;
    usleep(350000); // ~3 req/s rate limit
}

echo "Done. Created {$created} rows, skipped {$skipped} duplicates.\n";
echo "Database: https://notion.so/" . str_replace('-', '', $databaseId) . "\n";

function readCsv(string $path): array
{
    $handle = fopen($path, 'r');
    $header = fgetcsv($handle, escape: '\\');
    $rows = [];

    while (($data = fgetcsv($handle, escape: '\\')) !== false) {
        $rows[] = array_combine($header, $data);
    }

    fclose($handle);

    return $rows;
}

function buildPagePayload(string $databaseId, string $titleProp, array $properties, array $row): array
{
    $props = [
        $titleProp => [
            'title' => [[
                'type' => 'text',
                'text' => ['content' => ($row['Test Case ID'] ?? '') . ' — ' . ($row['Title'] ?? 'Untitled')],
            ]],
        ],
    ];

    $map = [
        'Test Case ID' => ['Test Case ID', 'ID', 'Test ID'],
        'Module' => ['Module'],
        'Title' => ['Title'],
        'Type' => ['Type'],
        'Priority' => ['Priority'],
        'Preconditions' => ['Preconditions'],
        'Steps' => ['Steps'],
        'Expected Result' => ['Expected Result', 'Expected'],
        'PHPUnit Reference' => ['PHPUnit Reference', 'Reference', 'PHPUnit'],
        'Automated' => ['Automated'],
        'Status' => ['Status'],
    ];

    foreach ($map as $csvCol => $candidates) {
        $value = trim((string) ($row[$csvCol] ?? ''));
        $propName = findProperty($properties, $candidates);

        if ($propName === null || $value === '') {
            continue;
        }

        $props[$propName] = notionValue($properties[$propName], $value);
    }

    return [
        'parent' => ['database_id' => $databaseId],
        'properties' => $props,
    ];
}

function notionValue(array $schema, string $value): array
{
    $type = $schema['type'] ?? 'rich_text';

    return match ($type) {
        'title' => ['title' => [[ 'type' => 'text', 'text' => ['content' => $value]]]],
        'rich_text' => ['rich_text' => [[ 'type' => 'text', 'text' => ['content' => $value]]]],
        'select' => ['select' => ['name' => $value]],
        'multi_select' => ['multi_select' => [['name' => $value]]],
        'checkbox' => ['checkbox' => in_array(strtolower($value), ['yes', 'true', '1'], true)],
        'url' => ['url' => $value],
        default => ['rich_text' => [[ 'type' => 'text', 'text' => ['content' => $value]]]],
    };
}

function findProperty(array $properties, array $candidates, ?string $type = null): ?string
{
    foreach ($candidates as $name) {
        if (isset($properties[$name])) {
            if ($type === null || ($properties[$name]['type'] ?? '') === $type) {
                return $name;
            }
        }
    }

    if ($type === 'title') {
        foreach ($properties as $name => $config) {
            if (($config['type'] ?? '') === 'title') {
                return $name;
            }
        }
    }

    return null;
}

function notionPageExists(string $databaseId, string $token, string $titleProp, string $testId): bool
{
    $body = [
        'filter' => [
            'property' => $titleProp,
            'title' => ['contains' => $testId],
        ],
        'page_size' => 1,
    ];

    $result = notionRequest('POST', "/databases/{$databaseId}/query", $token, $body);

    return ! empty($result['results']);
}

function notionRequest(string $method, string $path, string $token, ?array $body = null): array
{
    $ch = curl_init('https://api.notion.com/v1' . $path);
    $headers = [
        'Authorization: Bearer ' . $token,
        'Notion-Version: ' . NOTION_VERSION,
        'Content-Type: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);

    return json_decode($response ?: '{}', true) ?: [];
}

function formatUuid(string $id): string
{
    $id = str_replace('-', '', $id);

    if (strlen($id) !== 32) {
        return $id;
    }

    return substr($id, 0, 8) . '-'
        . substr($id, 8, 4) . '-'
        . substr($id, 12, 4) . '-'
        . substr($id, 16, 4) . '-'
        . substr($id, 20, 12);
}
