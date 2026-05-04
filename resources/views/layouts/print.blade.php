<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Cetak Struk' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* CSS Khusus untuk memanipulasi ukuran kertas Printer Thermal */
        @media print {
            body { background-color: white; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            @page { margin: 0; }
        }
    </style>
</head>
<body class="bg-gray-200 text-black font-mono antialiased flex justify-center p-4">
    {{ $slot }}
</body>
</html>