<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (Report-Only)
    |--------------------------------------------------------------------------
    |
    | Emits Content-Security-Policy-Report-Only for tuning before enforcement.
    | Filament/Livewire require 'unsafe-inline' and 'unsafe-eval' for scripts.
    |
    */

    'csp_report_only' => env('SECURITY_CSP_REPORT_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (Enforcing)
    |--------------------------------------------------------------------------
    |
    | Emits Content-Security-Policy alongside report-only (if enabled). The
    | policy is tuned to what Filament/Livewire/Alpine actually need
    | ('unsafe-inline' for inline script blocks; no 'unsafe-eval', no third-
    | party script CDNs), so it is enforced by default. Set
    | SECURITY_CSP_ENFORCE=false to fall back to report-only during a rollout
    | if a violation appears.
    |
    */

    'csp_enforce' => env('SECURITY_CSP_ENFORCE', true),

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Isolation Headers
    |--------------------------------------------------------------------------
    |
    | COOP: same-origin — no OAuth/payment popup flows in this app.
    | CORP: same-origin — resources are not intentionally served to other origins.
    |
    */

    'coop' => env('SECURITY_COOP', 'same-origin'),

    'corp' => env('SECURITY_CORP', 'same-origin'),

    /*
    |--------------------------------------------------------------------------
    | Health Check IP Allowlist
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs allowed to access /up in production (in addition to
    | localhost). Leave empty to block all non-local access.
    |
    */

    'health_check_allowed_ips' => env('HEALTH_CHECK_ALLOWED_IPS', ''),

    /*
    |--------------------------------------------------------------------------
    | Multi-Factor Authentication
    |--------------------------------------------------------------------------
    */

    'mfa_required_for_admin' => env('MFA_REQUIRED_FOR_ADMIN', true),

    /*
    |--------------------------------------------------------------------------
    | Security.txt
    |--------------------------------------------------------------------------
    */

    'contact' => env('SECURITY_CONTACT', 'security@skysyaz.my'),

    'expires' => env('SECURITY_TXT_EXPIRES', '2027-06-30T00:00:00.000Z'),

];
