<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir PoS</title>
    <link rel="icon" href="{{ asset('storage/logo.webp') }}" type="image/webp">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/pos-admin.tsx'])
</head>
<body class="bg-gray-100">
    <script src="https://unpkg.com/html5-qrcode" defer></script>

    @php
        $adminPath = trim((string) env('ADMIN_PATH', 'secure-panel-9x7k2'), '/');
    @endphp

    <div
        id="kasir-pos-react"
        data-endpoint-base="{{ url('/' . $adminPath . '/pos-api') }}"
        data-panel-url="{{ url('/' . $adminPath) }}"
        data-login-url="{{ url('/' . $adminPath . '/login') }}"
        class="min-h-screen"
    ></div>
</body>
</html>
