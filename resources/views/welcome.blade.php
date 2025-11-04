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

        <!-- Redirection SSO immédiate (si nécessaire) -->
        @if(isset($sso_redirect) && $sso_redirect)
        <script>
            // Redirection SSO immédiate AVANT que Vue.js ne charge
            // Ce script s'exécute immédiatement dans le head pour éviter que Vue.js ne se charge
            (function() {
                console.log('SSO redirect detected, redirecting immediately to:', '{{ $sso_redirect }}');
                // Utiliser window.location.replace pour ne pas ajouter à l'historique
                window.location.replace('{{ $sso_redirect }}');
            })();
        </script>
        @endif
        
        <!-- Styles / Scripts - Ne charger que si pas de redirection SSO -->
        @if(!isset($sso_redirect) || !$sso_redirect)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
        <!-- Styles minimaux pour la page de chargement -->
        <style>
            body { margin: 0; padding: 0; font-family: Inter, sans-serif; }
        </style>
        @endif
        
    </head>
    <body class="bg-gray-50 dark:bg-gray-900">
        @if(isset($sso_redirect) && $sso_redirect)
        <div id="app">
            <div style="display: flex; align-items: center; justify-content: center; height: 100vh; flex-direction: column; font-family: Inter, sans-serif;">
                <div style="border: 4px solid #f3f4f6; border-top: 4px solid #003366; border-radius: 50%; width: 48px; height: 48px; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 20px; color: #666; font-size: 14px;">Redirection vers le service externe...</p>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        </div>
        @else
        <div id="app"></div>
        @endif
    </body>
</html>