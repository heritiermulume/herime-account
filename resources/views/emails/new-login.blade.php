<x-mail::message>
<div style="text-align:center; margin-bottom:16px;">
    <img src="{{ asset('logo.png') }}" alt="HERIME" style="height:48px; width:auto;" />
    <div style="height:8px;"></div>
    <strong style="font-size:14px; color:#003366;">Compte Herime</strong>
</div>

# Nouvelle connexion

Bonjour {{ trim(($firstName ?? '').' '.($lastName ?? '')) ?: 'Utilisateur' }},

Une connexion a été détectée sur votre compte :

- IP : {{ $ip ?: 'Inconnue' }}
- Appareil/Navigateur : {{ $device ?: 'Inconnu' }}
- Date/Heure : {{ $time }}

Si vous êtes à l'origine de cette connexion, vous pouvez ignorer ce message.
Sinon, changez immédiatement votre mot de passe et contactez le support.

Merci,
L'équipe Herime
</x-mail::message>


