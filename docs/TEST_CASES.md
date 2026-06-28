# Quatriz TimeSheet — Test Case Catalog

Generated: 2026-06-28 06:09:54

Total automated tests: **144**

Run all tests: `php artisan test`

| ID | Module | Title | Type | Priority | PHPUnit Reference |
|----|--------|-------|------|----------|-------------------|
| TS-001 | Routing | That true is true | Unit | Medium | `Tests\Unit\ExampleTest::test_that_true_is_true` |
| TS-002 | UI | Generates csp safe data uri avatar | Unit | Low | `Tests\Unit\LocalAvatarProviderTest::test_generates_csp_safe_data_uri_avatar` |
| TS-003 | Approvals | Only assigned project manager can approve pm step | Unit | High | `Tests\Unit\ProjectApproverTest::test_only_assigned_project_manager_can_approve_pm_step` |
| TS-004 | Approvals | Only assigned project director can approve pd step | Unit | High | `Tests\Unit\ProjectApproverTest::test_only_assigned_project_director_can_approve_pd_step` |
| TS-005 | Settings | Get value with cast | Unit | Low | `Tests\Unit\SettingTest::test_get_value_with_cast` |
| TS-006 | Settings | Get value with numeric cast | Unit | Low | `Tests\Unit\SettingTest::test_get_value_with_numeric_cast` |
| TS-007 | Settings | Get value with string cast | Unit | Low | `Tests\Unit\SettingTest::test_get_value_with_string_cast` |
| TS-008 | Settings | Get value with array cast | Unit | Low | `Tests\Unit\SettingTest::test_get_value_with_array_cast` |
| TS-009 | Settings | Fillable attributes | Unit | Low | `Tests\Unit\SettingTest::test_fillable_attributes` |
| TS-010 | Timesheet Edit | Employee can edit own draft | Unit | High | `Tests\Unit\TimesheetAccessEditTest::test_employee_can_edit_own_draft` |
| TS-011 | Timesheet Edit | Employee can edit own rejected timesheet | Unit | High | `Tests\Unit\TimesheetAccessEditTest::test_employee_can_edit_own_rejected_timesheet` |
| TS-012 | Timesheet Edit | Employee cannot edit approved timesheet | Unit | High | `Tests\Unit\TimesheetAccessEditTest::test_employee_cannot_edit_approved_timesheet` |
| TS-013 | Timesheet Edit | Employee cannot edit other users draft | Unit | High | `Tests\Unit\TimesheetAccessEditTest::test_employee_cannot_edit_other_users_draft` |
| TS-014 | Timesheet Edit | Manager cannot edit draft timesheet | Unit | High | `Tests\Unit\TimesheetAccessEditTest::test_manager_cannot_edit_draft_timesheet` |
| TS-015 | Timesheet Edit | Admin can edit any editable timesheet | Unit | High | `Tests\Unit\TimesheetAccessEditTest::test_admin_can_edit_any_editable_timesheet` |
| TS-016 | Timesheet Edit | Admin can revert approved timesheet | Unit | High | `Tests\Unit\TimesheetAccessEditTest::test_admin_can_revert_approved_timesheet` |
| TS-017 | Timesheet Edit | Non admin cannot revert approved timesheet | Unit | High | `Tests\Unit\TimesheetAccessEditTest::test_non_admin_cannot_revert_approved_timesheet` |
| TS-018 | PDF Content | Weekly pdf signature fields reflect approvals | Unit | Medium | `Tests\Unit\TimesheetApprovalPdfTest::test_weekly_pdf_signature_fields_reflect_approvals` |
| TS-019 | PDF Content | Weekly pdf endpoint returns pdf with approver names | Unit | Medium | `Tests\Unit\TimesheetApprovalPdfTest::test_weekly_pdf_endpoint_returns_pdf_with_approver_names` |
| TS-020 | PDF Content | Weekly pdf view includes project role and tasks | Unit | Medium | `Tests\Unit\TimesheetApprovalPdfTest::test_weekly_pdf_view_includes_project_role_and_tasks` |
| TS-021 | Reports | Export url includes table filters | Unit | High | `Tests\Unit\TimesheetSummaryBuilderTest::test_export_url_includes_table_filters` |
| TS-022 | Reports | Query respects status and project filters | Unit | High | `Tests\Unit\TimesheetSummaryBuilderTest::test_query_respects_status_and_project_filters` |
| TS-023 | Reports | Reports builder matches grouping | Unit | High | `Tests\Unit\TimesheetSummaryBuilderTest::test_reports_builder_matches_grouping` |
| TS-024 | Reports | Query scopes project manager to assigned projects | Unit | High | `Tests\Unit\TimesheetSummaryBuilderTest::test_query_scopes_project_manager_to_assigned_projects` |
| TS-025 | Timesheet Model | Total hours with full week | Unit | Medium | `Tests\Unit\TimesheetTest::test_total_hours_with_full_week` |
| TS-026 | Timesheet Model | Total hours with empty week | Unit | Medium | `Tests\Unit\TimesheetTest::test_total_hours_with_empty_week` |
| TS-027 | Timesheet Model | Total hours with null hours | Unit | Medium | `Tests\Unit\TimesheetTest::test_total_hours_with_null_hours` |
| TS-028 | Timesheet Model | Total hours with partial week | Unit | Medium | `Tests\Unit\TimesheetTest::test_total_hours_with_partial_week` |
| TS-029 | Timesheet Model | Task for day returns daily task | Unit | Medium | `Tests\Unit\TimesheetTest::test_task_for_day_returns_daily_task` |
| TS-030 | Timesheet Model | Task for day falls back to notes when hours logged | Unit | Medium | `Tests\Unit\TimesheetTest::test_task_for_day_falls_back_to_notes_when_hours_logged` |
| TS-031 | Timesheet Model | Is draft | Unit | Medium | `Tests\Unit\TimesheetTest::test_is_draft` |
| TS-032 | Timesheet Model | Is pending pm | Unit | Medium | `Tests\Unit\TimesheetTest::test_is_pending_pm` |
| TS-033 | Timesheet Model | Is pending pd | Unit | Medium | `Tests\Unit\TimesheetTest::test_is_pending_pd` |
| TS-034 | Timesheet Model | Is approved | Unit | Medium | `Tests\Unit\TimesheetTest::test_is_approved` |
| TS-035 | Timesheet Model | Is rejected | Unit | Medium | `Tests\Unit\TimesheetTest::test_is_rejected` |
| TS-036 | Timesheet Model | Is editable returns true for draft and rejected | Unit | Medium | `Tests\Unit\TimesheetTest::test_is_editable_returns_true_for_draft_and_rejected` |
| TS-037 | Timesheet Model | Is submittable returns true for draft and rejected | Unit | Medium | `Tests\Unit\TimesheetTest::test_is_submittable_returns_true_for_draft_and_rejected` |
| TS-038 | User Model | Is admin returns true for admin role | Unit | Low | `Tests\Unit\UserTest::test_is_admin_returns_true_for_admin_role` |
| TS-039 | User Model | Is admin returns false for employee | Unit | Low | `Tests\Unit\UserTest::test_is_admin_returns_false_for_employee` |
| TS-040 | User Model | Is employee returns true | Unit | Low | `Tests\Unit\UserTest::test_is_employee_returns_true` |
| TS-041 | User Model | Is approver returns true for pm | Unit | Low | `Tests\Unit\UserTest::test_is_approver_returns_true_for_pm` |
| TS-042 | User Model | Can approve as pm | Unit | Low | `Tests\Unit\UserTest::test_can_approve_as_pm` |
| TS-043 | User Model | Can approve as pd | Unit | Low | `Tests\Unit\UserTest::test_can_approve_as_pd` |
| TS-044 | Validation | Accepts monday | Unit | Medium | `Tests\Unit\WeekStartsOnMondayTest::test_accepts_monday` |
| TS-045 | Validation | Rejects non monday | Unit | Medium | `Tests\Unit\WeekStartsOnMondayTest::test_rejects_non_monday` |
| TS-046 | Dashboard | Morning greeting in malaysia timezone | Unit | Low | `Tests\Unit\WelcomeBannerGreetingTest::test_morning_greeting_in_malaysia_timezone` |
| TS-047 | Dashboard | Afternoon greeting in malaysia timezone | Unit | Low | `Tests\Unit\WelcomeBannerGreetingTest::test_afternoon_greeting_in_malaysia_timezone` |
| TS-048 | Dashboard | Evening greeting in malaysia timezone | Unit | Low | `Tests\Unit\WelcomeBannerGreetingTest::test_evening_greeting_in_malaysia_timezone` |
| TS-049 | Audit Log | Timesheet status change is audited without sensitive fields | Feature | High | `Tests\Feature\ActivityLogTest::test_timesheet_status_change_is_audited_without_sensitive_fields` |
| TS-050 | Audit Log | Pm approval creates manual audit entry | Feature | High | `Tests\Feature\ActivityLogTest::test_pm_approval_creates_manual_audit_entry` |
| TS-051 | Audit Log | User role change is audited | Feature | High | `Tests\Feature\ActivityLogTest::test_user_role_change_is_audited` |
| TS-052 | Audit Log | Audit logger redacts sensitive properties | Feature | High | `Tests\Feature\ActivityLogTest::test_audit_logger_redacts_sensitive_properties` |
| TS-053 | Audit Log | Only admins can access audit log resource | Feature | High | `Tests\Feature\ActivityLogTest::test_only_admins_can_access_audit_log_resource` |
| TS-054 | Audit Log | Audit log page shows empty state when no entries | Feature | High | `Tests\Feature\ActivityLogTest::test_audit_log_page_shows_empty_state_when_no_entries` |
| TS-055 | Authentication | Guest is redirected to login | Feature | High | `Tests\Feature\AdminAuthTest::test_guest_is_redirected_to_login` |
| TS-056 | Authentication | Login page loads | Feature | High | `Tests\Feature\AdminAuthTest::test_login_page_loads` |
| TS-057 | Authentication | Authenticated user can access admin | Feature | High | `Tests\Feature\AdminAuthTest::test_authenticated_user_can_access_admin` |
| TS-058 | Audit Log | Backfill imports approval logs into activity log | Feature | Medium | `Tests\Feature\BackfillActivityLogTest::test_backfill_imports_approval_logs_into_activity_log` |
| TS-059 | Routing | The application redirects to admin login | Feature | Medium | `Tests\Feature\ExampleTest::test_the_application_redirects_to_admin_login` |
| TS-060 | Observability | Flare config redacts timesheet sensitive fields | Feature | Medium | `Tests\Feature\FlareIntegrationTest::test_flare_config_redacts_timesheet_sensitive_fields` |
| TS-061 | Observability | Flare reporting defaults off without credentials | Feature | Medium | `Tests\Feature\FlareIntegrationTest::test_flare_reporting_defaults_off_without_credentials` |
| TS-062 | Observability | Flare http sender posts errors to ingress when configured | Feature | Medium | `Tests\Feature\FlareIntegrationTest::test_flare_http_sender_posts_errors_to_ingress_when_configured` |
| TS-063 | Observability | Audit logger redaction matches flare policy | Feature | Medium | `Tests\Feature\FlareIntegrationTest::test_audit_logger_redaction_matches_flare_policy` |
| TS-064 | Observability | Admin can open audit log page | Feature | High | `Tests\Feature\ObservabilityAccessTest::test_admin_can_open_audit_log_page` |
| TS-065 | Observability | Non admin cannot open audit log page | Feature | High | `Tests\Feature\ObservabilityAccessTest::test_non_admin_cannot_open_audit_log_page` |
| TS-066 | PDF Authorization | Employee cannot download another employees weekly pdf | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_employee_cannot_download_another_employees_weekly_pdf` |
| TS-067 | PDF Authorization | Employee can download own weekly pdf | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_employee_can_download_own_weekly_pdf` |
| TS-068 | PDF Authorization | Project manager can download assigned project weekly pdf | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_project_manager_can_download_assigned_project_weekly_pdf` |
| TS-069 | PDF Authorization | Project manager cannot download unassigned project weekly pdf | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_project_manager_cannot_download_unassigned_project_weekly_pdf` |
| TS-070 | PDF Authorization | Project director can download assigned project weekly pdf | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_project_director_can_download_assigned_project_weekly_pdf` |
| TS-071 | PDF Authorization | Project director cannot download unassigned project weekly pdf | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_project_director_cannot_download_unassigned_project_weekly_pdf` |
| TS-072 | PDF Authorization | Admin can download any weekly pdf | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_admin_can_download_any_weekly_pdf` |
| TS-073 | PDF Authorization | Project manager summary export is scoped to assigned projects | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_project_manager_summary_export_is_scoped_to_assigned_projects` |
| TS-074 | PDF Authorization | Project director cannot export summary for unassigned project filter | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_project_director_cannot_export_summary_for_unassigned_project_filter` |
| TS-075 | PDF Authorization | Admin can export summary for all projects | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_admin_can_export_summary_for_all_projects` |
| TS-076 | PDF Authorization | Project manager cannot export summary for unassigned project filter | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_project_manager_cannot_export_summary_for_unassigned_project_filter` |
| TS-077 | PDF Authorization | Summary export rejects invalid group by | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_summary_export_rejects_invalid_group_by` |
| TS-078 | PDF Authorization | Summary export rejects invalid status | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_summary_export_rejects_invalid_status` |
| TS-079 | PDF Authorization | Summary export accepts valid filters | Feature | High | `Tests\Feature\PdfAuthorizationTest::test_summary_export_accepts_valid_filters` |
| TS-080 | PDF Export | Summary pdf honours status filter | Feature | High | `Tests\Feature\PdfSummaryExportTest::test_summary_pdf_honours_status_filter` |
| TS-081 | PDF Export | Summary pdf requires authentication | Feature | High | `Tests\Feature\PdfSummaryExportTest::test_summary_pdf_requires_authentication` |
| TS-082 | Projects | Project manager can create project and redirects to index | Feature | Medium | `Tests\Feature\ProjectCreateTest::test_project_manager_can_create_project_and_redirects_to_index` |
| TS-083 | Projects | Project manager sees all projects | Feature | High | `Tests\Feature\ProjectResourceAuthorizationTest::test_project_manager_sees_all_projects` |
| TS-084 | Projects | Project manager can view but not edit project created by someone else | Feature | High | `Tests\Feature\ProjectResourceAuthorizationTest::test_project_manager_can_view_but_not_edit_project_created_by_someone_else` |
| TS-085 | Projects | Project manager can edit project they created | Feature | High | `Tests\Feature\ProjectResourceAuthorizationTest::test_project_manager_can_edit_project_they_created` |
| TS-086 | Projects | Project manager can create projects | Feature | High | `Tests\Feature\ProjectResourceAuthorizationTest::test_project_manager_can_create_projects` |
| TS-087 | Projects | Project director can create projects | Feature | High | `Tests\Feature\ProjectResourceAuthorizationTest::test_project_director_can_create_projects` |
| TS-088 | Projects | Admin can edit any project | Feature | High | `Tests\Feature\ProjectResourceAuthorizationTest::test_admin_can_edit_any_project` |
| TS-089 | Projects | Project view route is registered | Feature | Medium | `Tests\Feature\ProjectViewRouteTest::test_project_view_route_is_registered` |
| TS-090 | Security | Security headers are present on login page | Feature | High | `Tests\Feature\SecurityHardeningTest::test_security_headers_are_present_on_login_page` |
| TS-091 | Security | Csp report only header is present when enabled | Feature | High | `Tests\Feature\SecurityHardeningTest::test_csp_report_only_header_is_present_when_enabled` |
| TS-092 | Security | Csp enforcing header is present when enabled | Feature | High | `Tests\Feature\SecurityHardeningTest::test_csp_enforcing_header_is_present_when_enabled` |
| TS-093 | Security | Security txt is publicly accessible | Feature | High | `Tests\Feature\SecurityHardeningTest::test_security_txt_is_publicly_accessible` |
| TS-094 | Security | Health check is allowed in testing environment | Feature | High | `Tests\Feature\SecurityHardeningTest::test_health_check_is_allowed_in_testing_environment` |
| TS-095 | Security | Health check is blocked in production for external ips | Feature | High | `Tests\Feature\SecurityHardeningTest::test_health_check_is_blocked_in_production_for_external_ips` |
| TS-096 | Security | Health check allows localhost in production | Feature | High | `Tests\Feature\SecurityHardeningTest::test_health_check_allows_localhost_in_production` |
| TS-097 | Security | User with valid role can access panel | Feature | High | `Tests\Feature\SecurityHardeningTest::test_user_with_valid_role_can_access_panel` |
| TS-098 | Security | User with invalid role cannot access panel | Feature | High | `Tests\Feature\SecurityHardeningTest::test_user_with_invalid_role_cannot_access_panel` |
| TS-099 | Security | Multi factor authentication is enabled on panel | Feature | High | `Tests\Feature\SecurityHardeningTest::test_multi_factor_authentication_is_enabled_on_panel` |
| TS-100 | Security | User model supports app authentication | Feature | High | `Tests\Feature\SecurityHardeningTest::test_user_model_supports_app_authentication` |
| TS-101 | Settings | Admin can update standard weekly hours | Feature | Medium | `Tests\Feature\SettingsPageTest::test_admin_can_update_standard_weekly_hours` |
| TS-102 | Settings | Non admin cannot access settings page | Feature | Medium | `Tests\Feature\SettingsPageTest::test_non_admin_cannot_access_settings_page` |
| TS-103 | Settings | Admin can reset user password | Feature | Medium | `Tests\Feature\SettingsPageTest::test_admin_can_reset_user_password` |
| TS-104 | Observability | Successful admin page view is recorded | Feature | Medium | `Tests\Feature\SiteTrafficTest::test_successful_admin_page_view_is_recorded` |
| TS-105 | Observability | Uptime endpoints are not counted as traffic | Feature | Medium | `Tests\Feature\SiteTrafficTest::test_uptime_endpoints_are_not_counted_as_traffic` |
| TS-106 | Observability | Admin can see traffic widget on dashboard | Feature | Medium | `Tests\Feature\SiteTrafficTest::test_admin_can_see_traffic_widget_on_dashboard` |
| TS-107 | Observability | Non admin cannot see traffic widget | Feature | Medium | `Tests\Feature\SiteTrafficTest::test_non_admin_cannot_see_traffic_widget` |
| TS-108 | Timesheet Workflow | Project manager can view timesheet they approved after reassignment | Feature | Medium | `Tests\Feature\TimesheetApproverHistoryTest::test_project_manager_can_view_timesheet_they_approved_after_reassignment` |
| TS-109 | Timesheet Workflow | Project manager sees analytics for all assigned projects | Feature | Medium | `Tests\Feature\TimesheetApproverHistoryTest::test_project_manager_sees_analytics_for_all_assigned_projects` |
| TS-110 | Timesheet Workflow | Reports page lists all active projects for project manager | Feature | Medium | `Tests\Feature\TimesheetApproverHistoryTest::test_reports_page_lists_all_active_projects_for_project_manager` |
| TS-111 | Timesheet Edit | Employee can access edit page for draft timesheet | Feature | High | `Tests\Feature\TimesheetEditAuthorizationTest::test_employee_can_access_edit_page_for_draft_timesheet` |
| TS-112 | Timesheet Edit | Employee cannot access edit page for approved timesheet | Feature | High | `Tests\Feature\TimesheetEditAuthorizationTest::test_employee_cannot_access_edit_page_for_approved_timesheet` |
| TS-113 | Timesheet Edit | Employee can access view page for approved timesheet | Feature | High | `Tests\Feature\TimesheetEditAuthorizationTest::test_employee_can_access_view_page_for_approved_timesheet` |
| TS-114 | Timesheet Edit | Admin revert makes timesheet editable for employee | Feature | High | `Tests\Feature\TimesheetEditAuthorizationTest::test_admin_revert_makes_timesheet_editable_for_employee` |
| TS-115 | Timesheet Edit | Employee cannot revert approved timesheet | Feature | High | `Tests\Feature\TimesheetEditAuthorizationTest::test_employee_cannot_revert_approved_timesheet` |
| TS-116 | Timesheet Edit | Print pdf remains available for approved timesheet | Feature | High | `Tests\Feature\TimesheetEditAuthorizationTest::test_print_pdf_remains_available_for_approved_timesheet` |
| TS-117 | Notifications | Submission notifies assigned project manager only | Feature | High | `Tests\Feature\TimesheetNotificationTest::test_submission_notifies_assigned_project_manager_only` |
| TS-118 | Notifications | Submission notifies admins when project manager not assigned | Feature | High | `Tests\Feature\TimesheetNotificationTest::test_submission_notifies_admins_when_project_manager_not_assigned` |
| TS-119 | Notifications | Pm approval notifies assigned project director only | Feature | High | `Tests\Feature\TimesheetNotificationTest::test_pm_approval_notifies_assigned_project_director_only` |
| TS-120 | Notifications | Final approval notifies employee | Feature | High | `Tests\Feature\TimesheetNotificationTest::test_final_approval_notifies_employee` |
| TS-121 | Notifications | Rejection notifies employee | Feature | High | `Tests\Feature\TimesheetNotificationTest::test_rejection_notifies_employee` |
| TS-122 | Notifications | Notifications are skipped when disabled | Feature | High | `Tests\Feature\TimesheetNotificationTest::test_notifications_are_skipped_when_disabled` |
| TS-123 | Authorization | Resource query scopes project manager to assigned projects | Feature | High | `Tests\Feature\TimesheetResourceAuthorizationTest::test_resource_query_scopes_project_manager_to_assigned_projects` |
| TS-124 | Authorization | Resource query scopes project director to assigned projects | Feature | High | `Tests\Feature\TimesheetResourceAuthorizationTest::test_resource_query_scopes_project_director_to_assigned_projects` |
| TS-125 | Authorization | Resource query allows admin to see all timesheets | Feature | High | `Tests\Feature\TimesheetResourceAuthorizationTest::test_resource_query_allows_admin_to_see_all_timesheets` |
| TS-126 | Timesheet Submit | Submit validation requires hours | Feature | High | `Tests\Feature\TimesheetSubmitActionTest::test_submit_validation_requires_hours` |
| TS-127 | Timesheet Submit | Submit updates status and creates approval log | Feature | High | `Tests\Feature\TimesheetSubmitActionTest::test_submit_updates_status_and_creates_approval_log` |
| TS-128 | Timesheet Submit | Employee can submit from view page | Feature | High | `Tests\Feature\TimesheetSubmitActionTest::test_employee_can_submit_from_view_page` |
| TS-129 | Timesheet Submit | Employee can submit from edit page | Feature | High | `Tests\Feature\TimesheetSubmitActionTest::test_employee_can_submit_from_edit_page` |
| TS-130 | Timesheet Submit | Submit action hidden for pending timesheet | Feature | High | `Tests\Feature\TimesheetSubmitActionTest::test_submit_action_hidden_for_pending_timesheet` |
| TS-131 | Timesheet Submit | Other employee cannot submit timesheet | Feature | High | `Tests\Feature\TimesheetSubmitActionTest::test_other_employee_cannot_submit_timesheet` |
| TS-132 | Timesheet Workflow | Employee can create timesheet | Feature | High | `Tests\Feature\TimesheetWorkflowTest::test_employee_can_create_timesheet` |
| TS-133 | Timesheet Workflow | Employee can submit timesheet | Feature | High | `Tests\Feature\TimesheetWorkflowTest::test_employee_can_submit_timesheet` |
| TS-134 | Timesheet Workflow | Pm can approve pending timesheet | Feature | High | `Tests\Feature\TimesheetWorkflowTest::test_pm_can_approve_pending_timesheet` |
| TS-135 | Timesheet Workflow | Pd can approve pending director timesheet | Feature | High | `Tests\Feature\TimesheetWorkflowTest::test_pd_can_approve_pending_director_timesheet` |
| TS-136 | Timesheet Workflow | Employee cannot see other timesheets | Feature | High | `Tests\Feature\TimesheetWorkflowTest::test_employee_cannot_see_other_timesheets` |
| TS-137 | Timesheet Workflow | Rejected timesheet is editable | Feature | High | `Tests\Feature\TimesheetWorkflowTest::test_rejected_timesheet_is_editable` |
| TS-138 | Timesheet Workflow | Approval flow without director | Feature | High | `Tests\Feature\TimesheetWorkflowTest::test_approval_flow_without_director` |
| TS-139 | Timesheet Workflow | Models have expected fillable fields | Feature | High | `Tests\Feature\TimesheetWorkflowTest::test_models_have_expected_fillable_fields` |
| TS-140 | Observability | Scheduler heartbeat requires valid token | Feature | High | `Tests\Feature\UptimeHeartbeatTest::test_scheduler_heartbeat_requires_valid_token` |
| TS-141 | Observability | Scheduler heartbeat returns service unavailable when stale | Feature | High | `Tests\Feature\UptimeHeartbeatTest::test_scheduler_heartbeat_returns_service_unavailable_when_stale` |
| TS-142 | Observability | Scheduler heartbeat returns ok after signal command | Feature | High | `Tests\Feature\UptimeHeartbeatTest::test_scheduler_heartbeat_returns_ok_after_signal_command` |
| TS-143 | Observability | Queue heartbeat returns ok after job runs | Feature | High | `Tests\Feature\UptimeHeartbeatTest::test_queue_heartbeat_returns_ok_after_job_runs` |
| TS-144 | Observability | Heartbeats return service unavailable when monitoring disabled | Feature | High | `Tests\Feature\UptimeHeartbeatTest::test_heartbeats_return_service_unavailable_when_monitoring_disabled` |

## Import guides

### Google Sheets (free)
1. Open [Google Sheets](https://sheets.google.com)
2. File → Import → Upload → select `docs/test-cases.csv`
3. Share the sheet with your team

### Notion (free)
1. Create a new page → Import → CSV
2. Upload `docs/test-cases.csv`
3. Notion creates a database you can filter by Module/Priority

### Jira (free up to 10 users)
1. Install **Zephyr Scale** or **Xray** (free trial) for test management, OR
2. Use **Jira Issues import**: Project Settings → Import → CSV (map Title, Description)
3. Import `docs/test-cases.csv` and map columns to custom fields

### GitHub (free, recommended with code)
Commit `docs/test-cases.csv` and `docs/TEST_CASES.md` to your repo for versioned reference.

