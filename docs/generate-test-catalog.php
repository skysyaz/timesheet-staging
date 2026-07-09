#!/usr/bin/env php
<?php

/**
 * Generates docs/test-cases.csv and docs/TEST_CASES.md from PHPUnit test list.
 * Run: php docs/generate-test-catalog.php
 */

$root = dirname(__DIR__);
chdir($root);

$output = shell_exec('php artisan test --list-tests 2>/dev/null') ?: '';
preg_match_all('/^ - (.+?)::(.+)$/m', $output, $matches, PREG_SET_ORDER);

$moduleMap = [
    'AdminAuthTest' => ['Authentication', 'High'],
    'ExampleTest' => ['Routing', 'Medium'],
    'PdfAuthorizationTest' => ['PDF Authorization', 'High'],
    'PdfSummaryExportTest' => ['PDF Export', 'High'],
    'SettingsPageTest' => ['Settings', 'Medium'],
    'TimesheetNotificationTest' => ['Notifications', 'High'],
    'TimesheetNotificationWorkflowTest' => ['Notifications', 'High'],
    'TimesheetResourceAuthorizationTest' => ['Authorization', 'High'],
    'TimesheetEditAuthorizationTest' => ['Timesheet Edit', 'High'],
    'TimesheetAccessEditTest' => ['Timesheet Edit', 'High'],
    'SecurityHardeningTest' => ['Security', 'High'],
    'TimesheetWorkflowTest' => ['Timesheet Workflow', 'High'],
    'TimesheetSubmitActionTest' => ['Timesheet Submit', 'High'],
    'TimesheetApproverHistoryTest' => ['Timesheet Workflow', 'Medium'],
    'ProjectCreateTest' => ['Projects', 'Medium'],
    'ProjectViewRouteTest' => ['Projects', 'Medium'],
    'ProjectResourceAuthorizationTest' => ['Projects', 'High'],
    'ActivityLogTest' => ['Audit Log', 'High'],
    'BackfillActivityLogTest' => ['Audit Log', 'Medium'],
    'ObservabilityAccessTest' => ['Observability', 'High'],
    'FlareIntegrationTest' => ['Observability', 'Medium'],
    'WatchtowerIntegrationTest' => ['Observability', 'Medium'],
    'UptimeHeartbeatTest' => ['Observability', 'High'],
    'SiteTrafficTest' => ['Observability', 'Medium'],
    'LocalAvatarProviderTest' => ['UI', 'Low'],
    'ProjectApproverTest' => ['Approvals', 'High'],
    'SettingTest' => ['Settings', 'Low'],
    'TimesheetApprovalPdfTest' => ['PDF Content', 'Medium'],
    'TimesheetSummaryBuilderTest' => ['Reports', 'High'],
    'TimesheetTest' => ['Timesheet Model', 'Medium'],
    'UserTest' => ['User Model', 'Low'],
    'WeekStartsOnMondayTest' => ['Validation', 'Medium'],
    'WelcomeBannerGreetingTest' => ['Dashboard', 'Low'],
];

$rows = [];
$id = 1;

foreach ($matches as $match) {
    $class = $match[1];
    $method = $match[2];
    $shortClass = class_basename($class);
    $type = str_contains($class, '\\Feature\\') ? 'Feature' : 'Unit';
    [$module, $priority] = $moduleMap[$shortClass] ?? ['General', 'Medium'];

    $title = ucfirst(str_replace('_', ' ', preg_replace('/^test_/', '', $method)));
    $reference = "{$class}::{$method}";

    $rows[] = [
        'id' => sprintf('TS-%03d', $id++),
        'module' => $module,
        'title' => $title,
        'type' => $type,
        'priority' => $priority,
        'preconditions' => inferPreconditions($shortClass, $method),
        'steps' => inferSteps($shortClass, $method),
        'expected' => inferExpected($shortClass, $method),
        'reference' => $reference,
        'automated' => 'Yes',
        'status' => 'Automated',
    ];
}

$csvPath = $root . '/docs/test-cases.csv';
$mdPath = $root . '/docs/TEST_CASES.md';

if (! is_dir($root . '/docs')) {
    mkdir($root . '/docs', 0755, true);
}

$fp = fopen($csvPath, 'w');
fputcsv($fp, [
    'Test Case ID',
    'Module',
    'Title',
    'Type',
    'Priority',
    'Preconditions',
    'Steps',
    'Expected Result',
    'PHPUnit Reference',
    'Automated',
    'Status',
], escape: '\\');

foreach ($rows as $row) {
    fputcsv($fp, [
        $row['id'],
        $row['module'],
        $row['title'],
        $row['type'],
        $row['priority'],
        $row['preconditions'],
        $row['steps'],
        $row['expected'],
        $row['reference'],
        $row['automated'],
        $row['status'],
    ], escape: '\\');
}

fclose($fp);

$md = "# Quatriz TimeSheet — Test Case Catalog\n\n";
$md .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
$md .= "Total automated tests: **" . count($rows) . "**\n\n";
$md .= "Run all tests: `php artisan test`\n\n";
$md .= "| ID | Module | Title | Type | Priority | PHPUnit Reference |\n";
$md .= "|----|--------|-------|------|----------|-------------------|\n";

foreach ($rows as $row) {
    $md .= sprintf(
        "| %s | %s | %s | %s | %s | `%s` |\n",
        $row['id'],
        $row['module'],
        $row['title'],
        $row['type'],
        $row['priority'],
        $row['reference'],
    );
}

$md .= "\n## Import guides\n\n";
$md .= "### Google Sheets (free)\n";
$md .= "1. Open [Google Sheets](https://sheets.google.com)\n";
$md .= "2. File → Import → Upload → select `docs/test-cases.csv`\n";
$md .= "3. Share the sheet with your team\n\n";
$md .= "### Notion (free)\n";
$md .= "1. Create a new page → Import → CSV\n";
$md .= "2. Upload `docs/test-cases.csv`\n";
$md .= "3. Notion creates a database you can filter by Module/Priority\n\n";
$md .= "### Jira (free up to 10 users)\n";
$md .= "1. Install **Zephyr Scale** or **Xray** (free trial) for test management, OR\n";
$md .= "2. Use **Jira Issues import**: Project Settings → Import → CSV (map Title, Description)\n";
$md .= "3. Import `docs/test-cases.csv` and map columns to custom fields\n\n";
$md .= "### GitHub (free, recommended with code)\n";
$md .= "Commit `docs/test-cases.csv` and `docs/TEST_CASES.md` to your repo for versioned reference.\n\n";

file_put_contents($mdPath, $md);

echo "Generated {$csvPath} (" . count($rows) . " test cases)\n";
echo "Generated {$mdPath}\n";

function inferPreconditions(string $class, string $method): string
{
    if (str_contains($class, 'Pdf') || str_contains($class, 'Auth') || str_contains($class, 'Workflow')) {
        return 'Users and projects seeded; authenticated session where required';
    }

    if (str_contains($class, 'Notification')) {
        return 'Mail/queue configured; notifications enabled in settings';
    }

    return 'Application database migrated; test fixtures created';
}

function inferSteps(string $class, string $method): string
{
    if (str_contains($method, 'cannot') || str_contains($method, 'rejects')) {
        return '1. Act as restricted user. 2. Perform forbidden action.';
    }

    if (str_contains($method, 'can_') || str_contains($method, 'accepts')) {
        return '1. Act as authorized user. 2. Perform allowed action.';
    }

    if (str_contains($class, 'Unit')) {
        return '1. Instantiate model/builder. 2. Execute method under test.';
    }

    return '1. Execute automated PHPUnit scenario.';
}

function inferExpected(string $class, string $method): string
{
    if (str_contains($method, 'cannot') || str_contains($method, 'rejects') || str_contains($method, 'non_admin_cannot')) {
        return 'Request is forbidden (403) or validation fails';
    }

    if (str_contains($method, 'redirect')) {
        return 'HTTP redirect to expected route';
    }

    if (str_contains($method, 'pdf') || str_contains($method, 'export')) {
        return 'HTTP 200 with application/pdf response';
    }

    if (str_contains($method, 'notifies')) {
        return 'Correct notification sent to intended recipients only';
    }

    return 'Assertion passes; behaviour matches business rule';
}

function class_basename(string $class): string
{
    $parts = explode('\\', $class);

    return end($parts);
}
