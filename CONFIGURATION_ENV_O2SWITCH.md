# ⚙️ Configuration complète du fichier .env sur O2Switch

## 📋 Valeurs à remplir dans le fichier .env

### Configuration MySQL O2Switch

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=herime_account
DB_USERNAME=votre_user_mysql
DB_PASSWORD=votre_mot_de_passe_mysql
```

---

## 🔧 Comment éditer le fichier .env

### Sur O2Switch (via SSH) :

```bash
# Se connecter
ssh votre-identifiant@o2switch.fr
cd www/votre-domaine.com

# Éditer le fichier
nano .env
```

### Dans nano, recherchez les lignes et remplissez :

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=herime_account
DB_USERNAME=votre_user_mysql
DB_PASSWORD=votre_mot_de_passe
```

### Enregistrer dans nano :
- `Ctrl + O` → Sauvegarder
- `Entrée` → Confirmer
- `Ctrl + X` → Quitter

---

## 📝 Exemple complet de section MySQL

```env
# Configuration MySQL O2Switch
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=heritiermulume_herime_account
DB_USERNAME=heritiermulume_herime
DB_PASSWORD=MonMotDePasseSecure123!
```

---

## ✅ Valeurs par défaut O2Switch

| Variable | Valeur | Description |
|----------|-------|-------------|
| `DB_CONNECTION` | `mysql` | Type de base de données |
| `DB_HOST` | `localhost` | Hôte MySQL (toujours localhost sur O2Switch) |
| `DB_PORT` | `3306` | Port MySQL (port par défaut) |
| `DB_DATABASE` | `herime_account` | Nom de votre base (peut être préfixé) |
| `DB_USERNAME` | `votre_user` | Identifiant MySQL O2Switch |
| `DB_PASSWORD` | `votre_mdp` | Mot de passe MySQL O2Switch |

---

## 🔍 Où trouver les valeurs DB_DATABASE, DB_USERNAME, DB_PASSWORD ?

### Via le panneau O2Switch :

1. Connectez-vous à https://www.o2switch.fr/
2. Allez dans **"Mon espace"** → **"Hébergement"**
3. Cliquez sur **"Bases de données"** ou **"MySQL"**
4. Vous verrez la liste avec :
   - **Nom de la base** → `DB_DATABASE`
   - **Utilisateur** → `DB_USERNAME`
   - **Mot de passe** → `DB_PASSWORD` (si vous l'avez défini)

### Via SSH MySQL :

```bash
# Se connecter à MySQL
mysql -u root -p

# Voir les bases de données
SHOW DATABASES;

# Voir les utilisateurs
SELECT User, Host FROM mysql.user;

# Quitter
EXIT;
```

---

## ⚠️ Important : Conventions O2Switch

O2Switch préfixe souvent les noms avec votre identifiant :

**Exemple** :
- Si votre identifiant O2Switch est `heritiermulume`
- La base pourrait être : `heritiermulume_herime_account`
- L'utilisateur pourrait être : `heritiermulume_herime` ou `heritiermulume`

**Vérifiez dans le panneau O2Switch** pour être sûr !

---

## 🧪 Vérifier la configuration

Après avoir modifié le `.env` :

```bash
# Vider le cache
php artisan config:clear
php artisan cache:clear

# Tester la connexion
php artisan db:show
```

Vous devriez voir :
```
MySQL ........................................................ 9.3.0
Connection ................................................... mysql
Database ............................................. herime_account
Host ........................................................ localhost
Port ........................................................... 3306
Username ................................................ votre_user
```

---

## 🆘 Si ça ne fonctionne toujours pas

### 1. Vérifier que le fichier .env est bien lu

```bash
cat .env | grep DB_
```

### 2. Vérifier qu'il n'y a pas d'espaces

```env
# ❌ MAUVAIS
DB_HOST = localhost
DB_DATABASE = herime_account

# ✅ BON
DB_HOST=localhost
DB_DATABASE=herime_account
```

### 3. Vérifier la connexion MySQL directe

```bash
mysql -u votre_user_mysql -p herime_account
```

Si cette commande échoue, le problème vient des identifiants MySQL, pas de Laravel.

---

## 📚 Ressources

- Consultez `CREATE_DATABASE_O2SWITCH.md` pour créer la base de données
- Consultez `RESOUDRE_ERREUR_ACCESS_DENIED.md` pour le dépannage

