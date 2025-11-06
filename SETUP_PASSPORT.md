# Configuration Passport pour HERIME SSO

## Problème
L'erreur "Personal access client not found for 'users' user provider" indique que Passport n'a pas de client d'accès personnel configuré.

## Solution

### 1. Vérifier que Passport est installé
```bash
php artisan passport:install
```

Cette commande va :
- Créer les clés de chiffrement Passport
- Créer les clients OAuth nécessaires

### 2. Si les clés existent déjà, créer uniquement le client personnel
```bash
php artisan passport:client --personal --name="Herime SSO Personal Access Client"
```

Quand on vous demande "Which user provider should this client use to retrieve users?", choisissez `users` (option 0).

### 3. Vérifier que le client a été créé
```bash
php artisan tinker
```

Puis dans tinker :
```php
\Laravel\Passport\Client::where('personal_access_client', true)->first();
```

Vous devriez voir un client avec `personal_access_client = 1`.

### 4. Si le problème persiste

Vérifiez que les clés Passport sont correctement configurées dans `.env` :
```env
PASSPORT_PRIVATE_KEY=""
PASSPORT_PUBLIC_KEY=""
```

Si ces clés sont vides, exécutez :
```bash
php artisan passport:keys
```

Puis copiez les clés générées dans `storage/` vers les variables d'environnement.

## Notes importantes

- Le client d'accès personnel doit être créé pour le provider `users`
- Les clés Passport doivent être générées et configurées
- Après création du client, redémarrer le serveur web si nécessaire

