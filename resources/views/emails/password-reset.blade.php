<x-mail::message>
<div style="text-align:center; margin-bottom:16px;">
    <img src="{{ asset('logo.png') }}" alt="HERIME" style="height:48px; width:auto;" />
    <div style="height:8px;"></div>
    <strong style="font-size:14px; color:#003366;">Compte Herime</strong>
</div>
# Réinitialisation de votre mot de passe

Bonjour {{ trim(($firstName ?? '').' '.($lastName ?? '')) ?: 'Utilisateur' }},

Vous avez demandé à réinitialiser votre mot de passe pour votre compte Herime.

Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :

<x-mail::button :url="$resetUrl">
Réinitialiser mon mot de passe
</x-mail::button>

Ce lien expirera dans **24 heures**.

Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email. Votre mot de passe restera inchangé.

**Important :** Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :
{{ $resetUrl }}

Cordialement,<br>
L'équipe Herime
</x-mail::message>
