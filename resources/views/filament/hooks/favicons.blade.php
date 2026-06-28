@php
    use App\Support\FaviconAssets;

    $favicon32 = FaviconAssets::url('favicon-32x32.png');
    $favicon16 = FaviconAssets::url('favicon-16x16.png');
    $appleTouch = FaviconAssets::url('apple-touch-icon.png');
    $manifest = asset('site.webmanifest?v=' . FaviconAssets::VERSION);
@endphp
{{-- Safari/Chrome/Firefox/Edge: PNG favicons only (ICO auto-fetch handled by public/favicon.ico) --}}
<link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon32 }}" data-app-favicon="1" />
<link rel="icon" type="image/png" sizes="16x16" href="{{ $favicon16 }}" data-app-favicon="1" />
<link rel="shortcut icon" type="image/png" href="{{ $favicon32 }}" data-app-favicon="1" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ $appleTouch }}" />
<link rel="manifest" href="{{ $manifest }}" />
<script>
(function () {
    var href = @json($favicon32);

    function applyFavicon() {
        document.querySelectorAll('link[rel="icon"]:not([data-app-favicon]), link[rel="shortcut icon"]:not([data-app-favicon])').forEach(function (el) {
            el.remove();
        });

        document.querySelectorAll('link[data-app-favicon]').forEach(function (link) {
            var base = href.split('?')[0];
            link.href = base + '?v={{ FaviconAssets::VERSION }}&_=' + Date.now();
        });
    }

    applyFavicon();
    document.addEventListener('DOMContentLoaded', applyFavicon);
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            applyFavicon();
        }
    });
    document.addEventListener('livewire:navigated', applyFavicon);
})();
</script>
