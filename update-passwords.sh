#!/bin/bash
GREEN='\033[0;32m'
NC='\033[0m'
log() { echo -e "${GREEN}[✓]${NC} $1"; }
echo "🔐 Mise à jour des mots de passe par défaut"
echo "=========================================="
php artisan tinker --execute="
\$users = \App\Models\User::whereIn('email', ['admin@example.com', 'test@example.com'])->get();
foreach(\$users as \$user) {
    \$user->password = \Illuminate\Support\Facades\Hash::make('Herime2024!');
    \$user->save();
    echo 'Mot de passe mis à jour pour: ' . \$user->email . PHP_EOL;
}
echo 'Terminé';
"
log "Mots de passe mis à jour !"
echo ""
echo "Nouveaux identifiants :"
echo "Email: admin@example.com"
echo "Mot de passe: Herime2024!"
