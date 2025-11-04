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
        @if(isset($sso_redirect) && !empty($sso_redirect))
        <script>
            // Redirection SSO immédiate AVANT que Vue.js ne charge
            // Ce script s'exécute immédiatement dans le head
            (function() {
                var redirectUrl = {!! json_encode($sso_redirect, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
                console.log('SSO redirect: Redirecting to', redirectUrl);
                
                // Redirection immédiate
                if (window.location && window.location.replace) {
                    window.location.replace(redirectUrl);
                } else if (window.location && window.location.href) {
                    window.location.href = redirectUrl;
                } else {
                    // Dernier recours : meta refresh sera exécuté par le navigateur
                    document.write('<meta http-equiv="refresh" content="0;url=' + redirectUrl + '">');
                }
            })();
        </script>
        <!-- Meta refresh comme fallback absolu -->
        <meta http-equiv="refresh" content="0;url={{ addslashes($sso_redirect) }}">
        @endif
        
        <!-- Styles / Scripts - Ne charger que si pas de redirection SSO -->
        @if(!isset($sso_redirect) || empty($sso_redirect))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
        <!-- Styles minimaux pour la page de chargement -->
        <style>
            body { margin: 0; padding: 0; font-family: Inter, sans-serif; background: #f9fafb; }
            #app { display: flex; align-items: center; justify-content: center; height: 100vh; }
        </style>
        @endif
        
    </head>
    <body class="bg-gray-50 dark:bg-gray-900">
        @if(isset($sso_redirect) && !empty($sso_redirect))
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
            <!-- Script de redirection dans le body aussi (double sécurité) -->
            <script>
                setTimeout(function() {
                    var redirectUrl = {!! json_encode($sso_redirect, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
                    if (window.location.href.indexOf('compte.herime.com') !== -1) {
                        console.log('Fallback redirect triggered');
                        window.location.replace(redirectUrl);
                    }
                }, 50);
            </script>
        </div>
        @else
        <div id="app"></div>
        @endif
    </body>
</html>