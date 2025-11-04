<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Compte Herime</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('icon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
    </head>
    <body class="bg-gray-50 dark:bg-gray-900">
        <div id="app"></div>
    </body>
</html>