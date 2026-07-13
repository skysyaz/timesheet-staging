<?php

use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\FaviconController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\TimesheetAttachmentController;
use App\Http\Controllers\UptimeHeartbeatController;
use Illuminate\Support\Facades\Route;

Route::get('/set-password/{token}', [SetPasswordController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('password.set');
Route::post('/set-password', [SetPasswordController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('password.update');

Route::permanentRedirect('/admin/login', '/login');
Route::permanentRedirect('/admin', '/');
Route::get('/admin/{path}', function (string $path) {
    return redirect('/'.ltrim($path, '/'), 301);
})->where('path', '.*');

Route::get('/favicon.ico', [FaviconController::class, 'legacyIco']);
Route::get('/apple-touch-icon.png', [FaviconController::class, 'legacyAppleTouch']);
Route::get('/apple-touch-icon-precomposed.png', fn () => redirect('/apple-touch-icon.png', 301));
Route::get('/branding/{file}', [FaviconController::class, 'branding'])
    ->where('file', 'favicon\.ico|favicon-16x16\.png|favicon-32x32\.png|apple-touch-icon\.png');
Route::get('/site.webmanifest', [FaviconController::class, 'manifest']);

Route::redirect('/favicon-16x16.png', '/branding/favicon-16x16.png', 301);
Route::redirect('/favicon-32x32.png', '/branding/favicon-32x32.png', 301);

Route::get('/.well-known/security.txt', function () {
    $contact = config('security.contact');
    $expires = config('security.expires');
    $canonical = url('/.well-known/security.txt');

    $body = implode("\n", [
        'Contact: mailto:'.$contact,
        'Expires: '.$expires,
        'Preferred-Languages: en',
        'Canonical: '.$canonical,
        'Policy: https://skysyaz.my/security-policy',
    ]);

    return response($body, 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
})->name('security.txt');

Route::middleware(['auth'])->group(function () {
    Route::get('/pdf/timesheet/{timesheet}', [PdfController::class, 'weekly'])->name('pdf.weekly');
    Route::get('/pdf/weekly-hours/{user}/{weekStart}', [PdfController::class, 'weeklyHours'])
        ->where('weekStart', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('pdf.weekly-hours');
    Route::get('/weekly-hours/print/{user}/{weekStart}', [PdfController::class, 'weeklyHoursPrint'])
        ->where('weekStart', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('weekly-hours.print');
    Route::get('/pdf/summary', [PdfController::class, 'summary'])->name('pdf.summary');
    Route::get('/timesheet-attachments/{attachment}/download', [TimesheetAttachmentController::class, 'download'])
        ->name('timesheet-attachments.download');
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/uptime/heartbeat', [UptimeHeartbeatController::class, 'scheduler'])
        ->name('uptime.heartbeat');
    Route::get('/uptime/queue-heartbeat', [UptimeHeartbeatController::class, 'queue'])
        ->name('uptime.queue-heartbeat');
});
