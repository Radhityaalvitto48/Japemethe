<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir PoS</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/pos-admin.tsx'])
</head>
<body class="bg-gray-100">
    <script src="https://unpkg.com/html5-qrcode" defer></script>

    <div
        id="kasir-pos-react"
        data-endpoint-base="{{ url('/admin/pos-api') }}"
        class="min-h-screen"
    ></div>
</body>
</html>
