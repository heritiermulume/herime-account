<x-mail::message>
<div style="text-align:center; margin-bottom:16px;">
    <img src="{{ asset('logo.png') }}" alt="HERIME" style="height:48px; width:auto;" />
    <div style="height:8px;"></div>
    <strong style="font-size:14px; color:#003366;">Compte Herime</strong>
</div>

# Compte désactivé

Bonjour {{ trim(($firstName ?? '').' '.($lastName ?? '')) ?: 'Utilisateur' }},

Votre compte a été désactivé. {{ $reason ? 'Raison : '.$reason : '' }}

Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'administrateur.

Merci,
L'équipe Herime
</x-mail::message>


