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
                console.log('[BLADE] SSO redirect: Redirecting to', redirectUrl);
                
                // Empêcher Vue Router de s'exécuter en marquant la redirection
                if (typeof sessionStorage !== 'undefined') {
                    sessionStorage.setItem('sso_redirecting', 'true');
                    sessionStorage.setItem('sso_redirect_url', redirectUrl);
                }
                
                // Redirection immédiate - utiliser replace() pour éviter l'historique
                // Ne PAS utiliser window.stop() car cela empêche la redirection elle-même
                console.log('[BLADE] Executing immediate redirect to:', redirectUrl);
                window.location.replace(redirectUrl);
            })();
        </script>
        <!-- Meta refresh comme fallback absolu -->
        <meta http-equiv="refresh" content="0;url={{ addslashes($sso_redirect) }}">
        @endif
        
        <!-- Script de test pour vérifier que JavaScript fonctionne -->
        <script>
            console.log('[BLADE] Template loaded, URL:', window.location.href);
            console.log('[BLADE] Has sso_redirect:', {{ isset($sso_redirect) && !empty($sso_redirect) ? 'true' : 'false' }});
            
            // Vérifier si on doit rediriger SSO (même si on est sur /dashboard)
            (function() {
                // Vérifier d'abord si une redirection SSO est déjà en cours
                if (typeof sessionStorage !== 'undefined' && sessionStorage.getItem('sso_redirecting') === 'true') {
                    const redirectUrl = sessionStorage.getItem('sso_redirect_url');
                    const params = new URLSearchParams(window.location.search);
                    const hasSSOParams = params.has('redirect') || params.has('force_token');
                    
                    // Si nous sommes revenus sur compte.herime.com sans paramètres SSO, arrêter la boucle
                    if (!hasSSOParams) {
                        sessionStorage.removeItem('sso_redirecting');
                        sessionStorage.removeItem('sso_redirect_url');
                    } else if (redirectUrl) {
                        console.log('[BLADE] SSO redirect already in progress, redirecting to:', redirectUrl);
                        window.location.replace(redirectUrl);
                        return;
                    }
                }
                
                const urlParams = new URLSearchParams(window.location.search);
                const forceToken = urlParams.get('force_token');
                const redirect = urlParams.get('redirect');
                
                // Si on a force_token et redirect dans l'URL, rediriger SSO
                if (forceToken && redirect) {
                    console.log('[BLADE] force_token and redirect detected, checking for SSO redirect');
                    
                    // Vérifier si on a déjà une redirection SSO du serveur
                    @if(isset($sso_redirect) && !empty($sso_redirect))
                    // Le serveur a déjà préparé la redirection, utiliser celle-ci
                    var redirectUrl = {!! json_encode($sso_redirect, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
                    console.log('[BLADE] Server provided SSO redirect, redirecting to:', redirectUrl);
                    
                    // Marquer la redirection en cours
                    if (typeof sessionStorage !== 'undefined') {
                        sessionStorage.setItem('sso_redirecting', 'true');
                        sessionStorage.setItem('sso_redirect_url', redirectUrl);
                    }
                    
                    window.location.replace(redirectUrl);
                    return;
                    @endif
                    
                    // Sinon, vérifier si l'utilisateur a un token dans localStorage
                    const token = localStorage.getItem('access_token');
                    console.log('[BLADE] Checking localStorage for token:', token ? 'FOUND' : 'NOT_FOUND');
                    
                    if (token) {
                        // L'utilisateur a un token, faire une requête au serveur pour générer le token SSO
                        console.log('[BLADE] User has token, requesting SSO redirect');
                        
                        // Marquer la redirection en cours AVANT la requête
                        if (typeof sessionStorage !== 'undefined') {
                            sessionStorage.setItem('sso_redirecting', 'true');
                        }
                        
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
                        .then(response => {
                            // Si erreur 401, vérifier le message pour savoir si le token est révoqué
                            if (!response.ok) {
                                return response.json().then(errorData => {
                                    console.log('[BLADE] API error:', response.status, errorData);
                                    
                                    // Si le message indique que le token est révoqué, le supprimer
                                    if (response.status === 401 && 
                                        (errorData.message?.includes('révoqué') || 
                                         errorData.message?.includes('revoked') ||
                                         errorData.message?.includes('Unauthenticated'))) {
                                        console.log('[BLADE] Token revoked, removing from localStorage');
                                        localStorage.removeItem('access_token');
                                    }
                                    
                                    if (typeof sessionStorage !== 'undefined') {
                                        sessionStorage.removeItem('sso_redirecting');
                                    }
                                    throw new Error(`API error: ${response.status} - ${errorData.message || 'Unknown error'}`);
                                });
                            }
                            return response.json();
                        })
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
                                    if (typeof sessionStorage !== 'undefined') {
                                        sessionStorage.removeItem('sso_redirecting');
                                    }
                                    return;
                                }
                                
                                console.log('[BLADE] SSO token generated, redirecting to:', redirectUrl);
                                
                                // Stocker l'URL de redirection
                                if (typeof sessionStorage !== 'undefined') {
                                    sessionStorage.setItem('sso_redirect_url', redirectUrl);
                                }
                                
                                // Rediriger immédiatement
                                window.location.replace(redirectUrl);
                            } else {
                                console.error('[BLADE] Failed to generate SSO token:', data);
                                if (typeof sessionStorage !== 'undefined') {
                                    sessionStorage.removeItem('sso_redirecting');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('[BLADE] Error generating SSO token:', error);
                            
                            if (typeof sessionStorage !== 'undefined') {
                                sessionStorage.removeItem('sso_redirecting');
                            }
                            // En cas d'erreur, ne PAS rediriger, laisser Vue.js charger le formulaire de login
                            console.log('[BLADE] Will show login form after error');
                        });
                    } else {
                        console.log('[BLADE] No token found in localStorage, user needs to login');
                        // Pas de token, laisser Vue.js charger le formulaire de login
                        // Ne PAS faire de redirection
                    }
                } else {
                    console.log('[BLADE] No force_token or redirect, normal page load');
                }
            })();
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
        <!-- Debug info (visible only in source) -->
        <!-- SSO_REDIRECT: {{ isset($sso_redirect) ? 'SET' : 'NOT_SET' }} -->
        <!-- SSO_REDIRECT_VALUE: {{ isset($sso_redirect) ? $sso_redirect : 'NONE' }} -->
        <!-- URL: {{ request()->fullUrl() }} -->
        
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
        <div id="app">
            <!-- Message de chargement par défaut (sera remplacé par Vue.js) -->
            <div style="display: flex; align-items: center; justify-content: center; height: 100vh; flex-direction: column; font-family: Inter, sans-serif;">
                <div style="border: 4px solid #f3f4f6; border-top: 4px solid #ffcc33; border-radius: 50%; width: 48px; height: 48px; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 20px; color: #666; font-size: 14px;">Chargement de l'application...</p>
                <p style="margin-top: 10px; color: #999; font-size: 12px;">Si cette page ne se charge pas, vérifiez votre connexion internet.</p>
            </div>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        </div>
        
        <!-- Script de détection d'erreur de chargement Vue.js -->
        <script>
            // Si Vue.js ne se charge pas après 10 secondes, afficher un message d'erreur
            setTimeout(function() {
                var app = document.getElementById('app');
                if (app && app.innerHTML.indexOf('Chargement de l\'application') !== -1) {
                    console.error('[ERROR] Vue.js failed to load after 10 seconds');
                    app.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100vh; flex-direction: column; font-family: Inter, sans-serif;">' +
                        '<div style="background: #fee; border: 1px solid #fcc; border-radius: 8px; padding: 20px; max-width: 500px; text-align: center;">' +
                        '<h2 style="color: #c00; margin: 0 0 10px 0;">Erreur de chargement</h2>' +
                        '<p style="color: #666; margin: 0;">L\'application n\'a pas pu se charger. Veuillez :</p>' +
                        '<ul style="text-align: left; color: #666; margin: 10px 0;">' +
                        '<li>Vider le cache de votre navigateur (Ctrl+Shift+R ou Cmd+Shift+R)</li>' +
                        '<li>Vérifier votre connexion internet</li>' +
                        '<li>Réessayer dans quelques instants</li>' +
                        '</ul>' +
                        '<button onclick="location.reload()" style="background: #003366; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 10px;">Recharger la page</button>' +
                        '</div>' +
                        '</div>';
                }
            }, 10000);
        </script>
        @endif
    </body>
</html>