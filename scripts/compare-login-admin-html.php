<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$kernel = app(Illuminate\Contracts\Http\Kernel::class);

$login = $kernel->handle(Illuminate\Http\Request::create('/admin/login', 'GET'));
$loginHtml = $login->getContent();

$user = App\Models\User::where('role', 'admin')->first();

if (! $user) {
    fwrite(STDERR, "No admin user found.\n");
    exit(1);
}

auth()->login($user);
$admin = $kernel->handle(Illuminate\Http\Request::create('/admin', 'GET'));
$adminHtml = $admin->getContent();

preg_match_all('#<link[^>]+rel=["\']?icon[^>]+>#i', $loginHtml, $loginIcons);
preg_match_all('#<link[^>]+rel=["\']?icon[^>]+>#i', $adminHtml, $adminIcons);

echo "LOGIN icon links:\n";
foreach ($loginIcons[0] as $link) {
    echo $link, PHP_EOL;
}

echo "\nADMIN icon links:\n";
foreach ($adminIcons[0] as $link) {
    echo $link, PHP_EOL;
}

preg_match_all('#<li[^>]*fi-sidebar-item[^>]*>[\s\S]*?</li>#', $adminHtml, $items);

echo "\nSidebar items:\n";
foreach ($items[0] as $item) {
    preg_match('#fi-sidebar-item-label[^>]*>\s*([^<]+)#', $item, $labelMatch);
    $label = trim(html_entity_decode($labelMatch[1] ?? '?'));
    $imgCount = preg_match_all('#<img[^>]+>#', $item);
    $svgCount = preg_match_all('#<svg[^>]+>#', $item);
    $xPath = substr_count($item, 'M6 18 18 6');
    echo "{$label}: imgs={$imgCount} svgs={$svgCount} xPath={$xPath}\n";
}

echo "\nAdmin img tags total: ", preg_match_all('#<img[^>]+>#', $adminHtml), PHP_EOL;
echo "Login img tags total: ", preg_match_all('#<img[^>]+>#', $loginHtml), PHP_EOL;
