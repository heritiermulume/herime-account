<x-mail::message>
# Réinitialisation de votre mot de passe

Bonjour,

Vous avez demandé à réinitialiser votre mot de passe pour votre compte {{ config('app.name') }}.

Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :

<x-mail::button :url="$resetUrl">
Réinitialiser mon mot de passe
</x-mail::button>

Ce lien expirera dans **24 heures**.

Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email. Votre mot de passe restera inchangé.

**Important :** Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :
{{ $resetUrl }}

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
