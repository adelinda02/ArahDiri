<!doctype html>
<html lang="id">
<head>
    {{-- Tema diterapkan sebelum CSS dimuat agar tidak berkedip (kunci localStorage sama dengan v14). --}}
    <script>
        try { document.documentElement.setAttribute('data-theme', localStorage.getItem('arahdiri-theme') || 'light'); } catch (e) {}
        document.addEventListener('error', function (e) {
            var el = e.target;
            if (el.tagName === 'IMG' && !el.src.endsWith('/fallback.svg')) { el.onerror = null; el.src = '/fallback.svg'; }
        }, true);
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="description" content="Kenali minat, jelajahi jurusan dan karier, lalu susun langkah masa depanmu bersama ArahDiri.">
    <link rel="icon" href="/brand-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Gabarito:wght@600;700;800&family=IBM+Plex+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>ArahDiri — Temukan arahmu</title>
    @inertiaHead
</head>
<body>
    @inertia('ad-root')
</body>
</html>
