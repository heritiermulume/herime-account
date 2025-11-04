<x-mail::message>
<div style="text-align:center; margin-bottom:16px;">
    <img src="{{ asset('logo.png') }}" alt="HERIME" style="height:48px; width:auto;" />
    <div style="height:8px;"></div>
    <strong style="font-size:14px; color:#003366;">Compte Herime</strong>
</div>

# Sécurité du compte

Bonjour {{ trim(($firstName ?? '').' '.($lastName ?? '')) ?: 'Utilisateur' }},

Votre mot de passe vient d'être modifié. Si vous êtes à l'origine de cette action, aucune autre démarche n'est nécessaire.

Si vous n'êtes pas à l'origine de cette modification, réinitialisez immédiatement votre mot de passe et contactez le support.

Merci,
L'équipe Herime
</x-mail::message>


