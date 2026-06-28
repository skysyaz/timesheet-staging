# Quatriz TimeSheet — Build Workflow

> Handoff document for AI-assisted development. All paths relative to `syazwan/build-app/timesheet-kilo`.

---

## 1. Project Overview

Admin-facing weekly timesheet application. Employees log hours per day (Mon–Sun) per project; timesheets flow through a two-tier approval chain (Project Manager → Project Director). PDF export, analytics/reports dashboard, audit trail (spatie/activitylog), email notifications (Resend), TOTP MFA for admins, and uptime monitoring heartbeats are all implemented.

---

## 2. Current State Assessment

### What exists (fully implemented)

| Area | Details |
|------|---------|
| **Auth** | Filament-based login with custom subheading, TOTP MFA (admin-required configurable), session-based |
| **User roles** | `employee`, `project_manager` (PM), `project_director` (PD), `admin` |
| **Models** | `User`, `Project`, `Timesheet`, `ApprovalLog`, `Setting` — all with fillables, casts, relations, role-check methods |
| **Migrations** | 14 migrations: users (role, color), cache, jobs, projects (code, name, status, pm/pd ids, creator), timesheets (user, project, week_start, hours JSON[7], tasks JSON[7], status, notes, unique constraint), approval_logs, settings, activity_log, plus performance indexes and multi-factor columns |
| **Filament Resources** | `TimesheetResource` (full CRUD + submit/approve/reject/revert actions, row-level visibility), `ProjectResource`, `UserResource`, `ActivityLogResource` |
| **Filament Pages** | `Dashboard` (custom), `Reports` (date/project filters, group-by, export PDF), `Settings` (weekly hours, director approval toggle, email toggle, admin-only) |
| **Filament Widgets** | `TimesheetStatsOverview` (total hours, approved, pending, overtime), `HoursByDayChart`, `HoursByProjectChart`, `WelcomeBanner` |
| **Form Components** | `DailyHoursGrid` (7-day float array, ValidDailyHours rule), `WeeklyTimesheetPlanner` (extends DailyHoursGrid), `DailyTasksGrid` (7-day string array) |
| **PDF export** | `PdfController` — weekly timesheet PDF (`barryvdh/laravel-dompdf`) with full approval signature block, summary PDF |
| **Notifications** | 4 mail notifications (Submitted, Pending Director, Approved, Rejected) — all queueable via `ShouldQueue`, gated by `Setting::emailNotificationsEnabled()` |
| **Notifications trait** | `BuildsTimesheetMail` — shared summary/viewUrl helpers |
| **Timesheet workflow** | `draft → submitted → pending_pm → pending_pd → approved`; or `rejected` at any point; admin-only revert-to-draft from approved |
| **Access control** | `TimesheetAccess` class — scope queries/view/edit/submit/approve/reject/revert per role, per project assignment |
| **Audit logging** | `AuditLogger` wrapping spatie/activitylog with property redaction |
| **Reports** | `TimesheetSummaryBuilder` — group by project/week/month with date/project/user/status filters, used by Reports page and PDF export |
| **Console** | `schedule:work` heartbeat signal, queue heartbeat job, activity log cleanup |
| **Controllers** | `PdfController`, `UptimeHeartbeatController` |
| **Security** | CSP (report-only + enforce), COOP/CORP, security.txt route, health check IP allowlist, MFA config |
| **Config** | 15 config files: app, auth, cache, database (sqlite default, pgsql for prod), filesystems, flare, logging, mail (Resend SMTP), observability, queue, security, services, session, ui, activitylog |
| **Database** | SQLite for dev, PostgreSQL for production (via `.env.example`), `database.sqlite` already present |
| **Frontend** | Vite + Tailwind CSS v4 + Filament v5.x, Inter font, SPA mode with exceptions for PDF and project routes |
| **Tests** | 18 Feature tests, 11 Unit tests — covering auth, projects, timesheets access/workflow/notifications/PDF, settings, heartbeats, activity log, observability, security hardening |
| **Seeder** | `DatabaseSeeder` — 6 users (all roles), 5 projects with PM/PD, 8 timesheets with various statuses, approval log entries |
| **Docker/deploy** | Composer `setup` script, `dev` script (concurrently runs server, queue, logs, vite), GitHub-flavoured README |

### What is missing or incomplete

- **Missing `created_by` in seeder**: Projects seeded without `created_by` will cause `creator()->name` to return null.
- **Missing `project_role` and `tasks` data in seeder**: Timesheets seeder does not populate these columns (added by migration `2026_06_27_000001`) despite the form requiring `project_role`.
- **`resources/js/filament/ui-interactivity.js`**: Referenced in `vite.config.js` input — likely missing or empty.
- **`resources/css/filament/admin/theme.css`**: Referenced in `AdminPanelProvider` — may need creation.
- **Logo**: `public/logo.webp` referenced by `brandLogo` — may not exist.
- **No `ProjectFactory` / `TimesheetFactory` / `ApprovalLogFactory`**: Only `UserFactory` exists.
- **No `failed_jobs` table**: Queue worker cannot track failed jobs.
- **Policies directory**: Empty — all authorization inline in Resources rather than Laravel policies.
- **Listeners directory**: Empty — notifications triggered inline from Resource actions.
- **Role-transition boundary**: No formal `ProjectPolicy` or `TimesheetPolicy` registered.

---

## 3. Prerequisites

- **PHP**: ^8.3 (8.4 on VPS)
- **Composer**: v2+
- **Node.js**: 20+
- **npm**: 9+
- **Database**: SQLite (dev) or PostgreSQL 15+ (prod)

---

## 4. Tech Stack Summary

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | ^13.8 |
| PHP | PHP | ^8.3 |
| Admin panel | Filament | ^5.6 |
| PDF | barryvdh/laravel-dompdf | ^3.1 |
| Audit | spatie/laravel-activitylog | ^5.0 |
| Error tracking | spatie/laravel-flare | ^3.0 |
| Database (dev) | SQLite | — |
| Database (prod) | PostgreSQL | 15+ |
| Frontend build | Vite | ^8.0 |
| CSS | Tailwind CSS | ^4.0 |
| Mail | Resend (SMTP) | — |
| Queue | Database driver | — |

---

## 5. Architecture & File Structure

```
timesheet/
├── app/
│   ├── Console/
│   ├── Filament/
│   │   ├── Actions/              # ResetUserPasswordAction
│   │   ├── Concerns/             # ConfiguresTableToolbar, RestoresHeaderInteractivity
│   │   ├── Forms/Components/     # DailyHoursGrid, DailyTasksGrid, WeeklyTimesheetPlanner
│   │   ├── Pages/                # Dashboard, Reports, Settings; Auth/Login
│   │   ├── Resources/            # TimesheetResource, ProjectResource, UserResource, ActivityLogResource
│   │   │   └── */Pages/          # List, Create, Edit, View per resource
│   │   └── Widgets/              # TimesheetStatsOverview, HoursByDayChart, HoursByProjectChart, WelcomeBanner
│   ├── Http/
│   │   ├── Controllers/          # PdfController, UptimeHeartbeatController
│   │   └── Middleware/
│   ├── Jobs/                     # RecordQueueHeartbeat
│   ├── Models/
│   │   ├── Concerns/LogsAuditableChanges
│   │   ├── User.php              # FilamentUser, HasAppAuthentication, role checks
│   │   ├── Project.php           # code, name, status, pm/pd/creator FKs
│   │   ├── Timesheet.php         # week_start, hours[JSON7], tasks[JSON7], status workflow
│   │   ├── ApprovalLog.php       # timesheet_id, user_id, action, comment
│   │   └── Setting.php           # key-value store (JSON value)
│   ├── Notifications/            # 4 mail classes (all ShouldQueue)
│   ├── Policies/                 # (empty)
│   ├── Providers/                # AppServiceProvider, Filament/AdminPanelProvider
│   ├── Rules/                    # ValidDailyHours, WeekStartsOnMonday
│   └── Support/
│       ├── Concerns/BuildsTimesheetMail
│       ├── AuditLogger.php
│       ├── LocalAvatarProvider.php
│       ├── TimesheetAccess.php   # RBAC query scoping + permission checks
│       ├── TimesheetNotifier.php
│       └── TimesheetSummaryBuilder.php
├── bootstrap/
├── config/                       # 15 config files
├── database/
│   ├── factories/                # UserFactory only
│   ├── migrations/               # 14 migrations
│   ├── seeders/DatabaseSeeder.php
│   └── database.sqlite
├── docs/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/                    # Blade: filament/forms/components, hooks, pages, widgets; pdf/
├── routes/
│   ├── web.php                   # /admin/login redirect, /pdf/*, /uptime/*, security.txt
│   └── console.php               # schedule heartbeat + queue heartbeat + activitylog:clean
├── tests/
│   ├── Feature/                  # 18 tests
│   ├── Unit/                     # 11 tests
│   └── TestCase.php
├── .env.example                  # Full env template
├── composer.json
├── package.json
├── vite.config.js
└── README.md
```

---

## 6. Build & Run Instructions

### 6.1 Initial Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install && npm run build
php artisan storage:link
```

### 6.2 Development

```bash
# All services (server, queue, logs, Vite HMR)
npm run dev

# Or individual terminals:
php artisan serve              # Terminal 1
php artisan queue:work         # Terminal 2
php artisan pail --timeout=0   # Terminal 3
npm run dev                    # Terminal 4

# Scheduler
php artisan schedule:work
```

### 6.3 Testing

```bash
php artisan test
php artisan test tests/Feature/TimesheetWorkflowTest.php   # single file
php artisan test tests/Unit                                 # unit only
php artisan test --coverage                                 # with coverage
```

### 6.4 Production

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

---

## 7. Feature Implementation Workflow

### Step 1: Seed `project_role` and `tasks` data

**Files**: `database/seeders/DatabaseSeeder.php`

Add `project_role` (string) and `tasks` (7-element string array) to each entry in `$tsData`. Example:

```php
['user_id' => 1, 'project_id' => 1, 'project_role' => 'Site Engineer',
 'week_start' => $twoWeeksAgo, 'hours' => [8,8,8,8,8,0,0],
 'tasks' => ['Module A', 'Module B', 'Integration', '', '', '', ''],
 'status' => 'approved', 'notes' => 'ERP module integration complete.'],
```

**Validation**: Re-seed → `Timesheet::first()->project_role` is non-null, `tasks` is 7-element array.

### Step 2: Seed `created_by` on projects

**Files**: `database/seeders/DatabaseSeeder.php`

After creating each project, set `created_by` to the admin user ID:

```php
$createdProjects[0]->update(['created_by' => $createdUsers[5]->id]);
```

**Validation**: Re-seed → `Project::first()->creator->name === 'Admin'`.

### Step 3: Create missing factories

**Files to create**:
- `database/factories/ProjectFactory.php`
- `database/factories/TimesheetFactory.php`
- `database/factories/ApprovalLogFactory.php`

**ProjectFactory**:
```php
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->lexify('???-??')),
            'name' => fake()->sentence(3),
            'status' => 'active',
        ];
    }
}
```

**TimesheetFactory**: Generate `user_id`, `project_id`, `week_start` (Monday), `hours` (array 7 floats), `tasks` (array 7 strings), `status` default `'draft'`, `project_role`.

**ApprovalLogFactory**: `timesheet_id`, `user_id`, `action`.

**Validation**: `Project::factory()->create()` succeeds in `php artisan tinker`.

### Step 4: Add failed_jobs table

```bash
php artisan queue:failed-table
php artisan migrate
```

**Validation**: `failed_jobs` table exists.

### Step 5: Create asset stubs (if missing)

Check for these files; create if absent:

**`resources/css/app.css`**:
```css
@import "tailwindcss";
```

**`resources/css/filament/admin/theme.css`**:
```css
@import "tailwindcss";
@import "../../../vendor/filament/filament/resources/css/app.css";
```

**`resources/js/app.js`**: `import './bootstrap';`

**`resources/js/filament/ui-interactivity.js`**:
```js
document.addEventListener('livewire:navigated', () => {});
```

**Validation**: `npm run build` completes.

### Step 6: Create `public/logo.webp` placeholder

```bash
php -r "imagewebp(imagecreatetruecolor(1,1), 'public/logo.webp', 100);"
```

**Validation**: `file_exists(public_path('logo.webp'))` is `true`.

### Step 7: Create performance index

**File**: `database/migrations/2026_06_28_000001_add_report_indexes.php`

```php
Schema::table('timesheets', function (Blueprint $table) {
    $table->index(['status', 'week_start'], 'timesheets_status_week_start_idx');
});
```

**Validation**: `php artisan migrate` succeeds.

### Step 8: Add `TimesheetPolicy` (optional)

**File**: `app/Policies/TimesheetPolicy.php`

```php
class TimesheetPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Timesheet $timesheet): bool
    {
        return TimesheetAccess::userCanViewTimesheet($user, $timesheet);
    }

    public function create(User $user): bool { return true; }

    public function update(User $user, Timesheet $timesheet): bool
    {
        return TimesheetAccess::userCanEditTimesheet($user, $timesheet);
    }

    public function delete(User $user, Timesheet $timesheet): bool
    {
        return $timesheet->isDraft()
            && TimesheetAccess::userCanEditTimesheet($user, $timesheet);
    }
}
```

Register in `App\Providers\AppServiceProvider`.

**Validation**: All existing tests pass.

### Step 9: Full smoke test

```bash
rm -f database/database.sqlite && touch database/database.sqlite
php artisan migrate --seed
php artisan serve &
# Login as admin@company.com / pass123
# Run full workflow: create → submit → approve PM → approve PD → export PDF
php artisan test
```

**Validation**: All 29+ tests pass; manual workflow succeeds.

---

## 8. Data Model

### 8.1 Schema

```
users
  id (PK), name, email (unique), email_verified_at, password,
  role (employee|project_manager|project_director|admin),
  color (#hex), remember_token, timestamps

projects
  id (PK), code (unique), name, status (active|inactive),
  project_manager_id (FK→users), project_director_id (FK→users),
  created_by (FK→users), timestamps

timesheets
  id (PK), user_id (FK→users), project_id (FK→projects),
  project_role (varchar), week_start (date), hours (json),
  tasks (json), status (draft|pending_pm|pending_pd|approved|rejected),
  notes (text), timestamps
  UNIQUE(user_id, project_id, week_start)

approval_logs
  id (PK), timesheet_id (FK→timesheets), user_id (FK→users),
  action (varchar), comment (text), timestamps

settings
  id (PK), key (unique), value (json)

activity_log
  (spatie/laravel-activitylog default schema)
```

### 8.2 Status workflow

```
draft ──submit──> pending_pm ──PM approve──> pending_pd ──PD approve──> approved
                    │                            │
                    └──reject──> rejected         └──reject──> rejected

approved ──admin revert──> draft
```

### 8.3 Routes

| Method | URI | Auth | Purpose |
|--------|-----|------|---------|
| GET | `/` | No | Redirect to `/admin/login` |
| GET | `/admin/*` | Yes | Filament panel |
| GET | `/pdf/timesheet/{timesheet}` | Yes | Weekly timesheet PDF |
| GET | `/pdf/summary` | Yes | Summary PDF (query params) |
| GET | `/uptime/heartbeat` | Throttled | Scheduler heartbeat |
| GET | `/uptime/queue-heartbeat` | Throttled | Queue heartbeat |
| GET | `/.well-known/security.txt` | No | Security contact |
| GET | `/up` | No | Health check (IP-restricted) |

---

## 9. Testing Strategy

### 9.1 Existing tests (29 total)

**Feature (18)**: ActivityLog, AdminAuth, Example, FlareIntegration, ObservabilityAccess, PdfAuthorization, PdfSummaryExport, ProjectCreate, ProjectResourceAuthorization, ProjectViewRoute, SecurityHardening, SettingsPage, TimesheetApproverHistory, TimesheetEditAuthorization, TimesheetNotification, TimesheetResourceAuthorization, TimesheetWorkflow, UptimeHeartbeat.

**Unit (11)**: Example, LocalAvatarProvider, ProjectApprover, Setting, TimesheetAccessEdit, TimesheetApprovalPdf, TimesheetSummaryBuilder, Timesheet, User, WeekStartsOnMonday, WelcomeBannerGreeting.

### 9.2 Tests to add

| # | Test | Type | Coverage |
|---|------|------|----------|
| 1 | `TimesheetFactoryTest` | Unit | Factory creates valid timesheet |
| 2 | `ProjectFactoryTest` | Unit | Factory creates valid project |
| 3 | `ApprovalLogFactoryTest` | Unit | Factory creates valid log |
| 4 | `TimesheetWorkflowRevertTest` | Feature | Admin revert approved → draft |
| 5 | `TimesheetProjectRoleTest` | Feature | project_role display in view |
| 6 | `TimesheetTaskDayFallbackTest` | Unit | taskForDay() fallback to notes |
| 7 | `DatabaseSeederTest` | Feature | Correct counts after seed |
| 8 | `SettingsFeatureTest` | Feature | Toggle reflects in notifier |

### 9.3 Run

```bash
php artisan test
```

---

## 10. Common Pitfalls & Mitigations

| # | Pitfall | Mitigation |
|---|---------|------------|
| 1 | Seeder missing `project_role` → new timesheet form breaks on PM/PD screens | Step 1 |
| 2 | Seeder missing `created_by` → `creator()` returns null | Step 2 |
| 3 | No factories → tests cannot create test data systematically | Step 3 |
| 4 | No `failed_jobs` table → queue worker cannot retry | Step 4 |
| 5 | Missing Vite entry points → `npm run build` errors | Step 5 |
| 6 | Missing logo → 404 in browser console | Step 6 |
| 7 | Missing report index → slow queries under high volume | Step 7 |
| 8 | Inline authorization → inconsistent gate checks across Resources | Step 8 (optional) |
| 9 | `QUEUE_CONNECTION=database` but `php artisan queue:table` not run → queue worker may fail | Step 4 handles `failed_jobs`; confirm `jobs` migration exists (`0001_01_01_000002_create_jobs_table.php`) |
| 10 | `ACTIVITYLOG_ENABLED=true` but no `activity_log` table → migration `2026_06_28_130421` handles this | Confirm migration has run |

---

## 11. Handoff Checklist

- [ ] `git init && git add -A && git commit -m "Initial scaffold"`
- [ ] Step 1: Seeder includes `project_role` and `tasks`
- [ ] Step 2: Seeder sets `created_by` on all projects
- [ ] Step 3: `ProjectFactory`, `TimesheetFactory`, `ApprovalLogFactory` exist
- [ ] Step 4: `failed_jobs` table migrated
- [ ] Step 5: All Vite entry points exist and `npm run build` succeeds
- [ ] Step 6: `public/logo.webp` exists (or real logo placed)
- [ ] Step 7: Report index migration applied
- [ ] Step 8: `TimesheetPolicy` registered (optional but recommended)
- [ ] Step 9: `php artisan test` passes all tests (existing + new)
- [ ] Step 9: Manual smoke test — login, create timesheet, submit, approve (PM+PD), export PDF, check reports
- [ ] `composer audit` passes (no vulnerable deps)
