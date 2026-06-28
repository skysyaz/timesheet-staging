# Quatriz TimeSheet — Latest Changes (28 Jun 2026)

## Timesheet submit from View / Edit

- **Submit** button added to **View Timesheet** and **Edit Timesheet** header actions (Option A: standalone action bar button)
- Shared validation before submit: project, role, Monday week start, valid daily hours, total hours > 0
- Edit screen validates form, saves silently, submits, then redirects to View with success notification
- List-page Submit refactored to use the same `submitTimesheet()` path (no behaviour change)
- **6 new tests** in `Tests\Feature\TimesheetSubmitActionTest`

## Observability & reporting (prior release)

- Audit log backfill command: `php artisan activitylog:backfill-approval-logs`
- Site traffic dashboard widget (admin-only) with daily aggregation
- Empty state on Audit Log page when no entries exist

## Test suite

- **144 automated tests** (was 138)
- Regenerate catalog: `php docs/generate-test-catalog.php`

Sync to Notion:

```bash
export NOTION_TOKEN="your_token"
export NOTION_DATABASE_ID="8a255339-1e69-4304-9c6f-daa91105a0a0"
php docs/import-to-notion.php
```

Database: [Test Cases on Notion](https://www.notion.so/8a2553391e6943049c6fdaa91105a0a0)

## Production

- **https://timesheet.quatriz-sd.my**
- VPS path: `/var/www/timesheet`
