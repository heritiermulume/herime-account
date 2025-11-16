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
        
        <!-- Script de test pour vérifier que JavaScript fonctionne -->
        <script>
            console.log('[BLADE] Template loaded, URL:', window.location.href);
            console.log('[BLADE] Has sso_redirect:', {{ isset($sso_redirect) && !empty($sso_redirect) ? 'true' : 'false' }});
            
            // Si on a force_token dans l'URL et qu'on n'a pas de sso_redirect, vérifier le token
            @if(!isset($sso_redirect) || empty($sso_redirect))
            (function() {
                const urlParams = new URLSearchParams(window.location.search);
                const forceToken = urlParams.get('force_token');
                const redirect = urlParams.get('redirect');
                
                if (forceToken && redirect) {
                    // Vérifier si l'utilisateur a un token dans localStorage
                    const token = localStorage.getItem('access_token');
                    if (token) {
                        // L'utilisateur a un token, faire une requête au serveur pour générer le token SSO
                        console.log('[BLADE] User has token, requesting SSO redirect');
                        
                        // Construire l'URL de l'API
                        const apiUrl = '/api/sso/generateToken';
                        
                        // Faire une requête pour générer le token SSO
                        fetch(apiUrl, {
                            method: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                redirect: redirect
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data) {
                                // Utiliser callback_url si disponible, sinon construire l'URL avec le token
                                let redirectUrl;
                                if (data.data.callback_url) {
                                    redirectUrl = data.data.callback_url;
                                } else if (data.data.token) {
                                    const redirectUrlObj = new URL(redirect);
                                    redirectUrlObj.searchParams.set('token', data.data.token);
                                    redirectUrl = redirectUrlObj.toString();
                                } else {
                                    console.error('[BLADE] No token or callback_url in response:', data);
                                    return;
                                }
                                
                                console.log('[BLADE] SSO token generated, redirecting to:', redirectUrl);
                                
                                // Rediriger immédiatement
                                window.location.replace(redirectUrl);
                            } else {
                                console.error('[BLADE] Failed to generate SSO token:', data);
                            }
                        })
                        .catch(error => {
                            console.error('[BLADE] Error generating SSO token:', error);
                        });
                    }
                }
            })();
            @endif
        </script>
        
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