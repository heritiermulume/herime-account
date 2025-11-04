# 🔧 Formats de connexion O2Switch

## Formats possibles pour SSH/SCP sur O2Switch

### Format 1 : Avec identifiant serveur
```bash
ssh muhe3594@persil.o2switch.fr
scp -r public/build/ muhe3594@persil.o2switch.fr:/home/muhe3594/herime-account/public/
```

### Format 2 : Avec ssh.o2switch.net
```bash
ssh muhe3594@ssh.o2switch.net
scp -r public/build/ muhe3594@ssh.o2switch.net:/home/muhe3594/herime-account/public/
```

### Format 3 : Avec IP (si disponible)
```bash
# Remplacer XXX.XXX.XXX.XXX par l'IP du serveur
ssh muhe3594@XXX.XXX.XXX.XXX
scp -r public/build/ muhe3594@XXX.XXX.XXX.XXX:/home/muhe3594/herime-account/public/
```

## 🔍 Comment trouver le bon format

### 1. Vérifier dans votre panneau O2Switch
- Connexion SSH → Format indiqué dans le panneau
- Informations de connexion → Hostname exact

### 2. Tester différents formats
```bash
# Tester format 1
ssh muhe3594@persil.o2switch.fr

# Tester format 2
ssh muhe3594@ssh.o2switch.net

# Tester avec IP
ssh muhe3594@IP_DU_SERVEUR
```

### 3. Vérifier les DNS
```bash
# Tester la résolution DNS
nslookup persil.o2switch.fr
nslookup ssh.o2switch.net
```

## ✅ Solution alternative : Utiliser rsync ou FTP

### Option 1 : Via FTP/SFTP (FileZilla, Cyberduck, etc.)
1. Connectez-vous avec FileZilla ou Cyberduck
2. Transférez le dossier `public/build/` vers `/home/muhe3594/herime-account/public/`

### Option 2 : Compiler directement sur le serveur
```bash
# Se connecter en SSH (quel que soit le format qui fonctionne)
ssh muhe3594@[hostname-qui-fonctionne]

cd /home/muhe3594/herime-account

# Si Node.js est disponible
npm install --production
npm run build

# Sinon, utiliser l'option 1 (transfert FTP)
```

## 📋 Commandes de test

```bash
# Test 1 : Format persil.o2switch.fr
ping -c 1 persil.o2switch.fr

# Test 2 : Format ssh.o2switch.net
ping -c 1 ssh.o2switch.net

# Test 3 : Résolution DNS
host persil.o2switch.fr
host ssh.o2switch.net
```

## 🎯 Solution recommandée

1. **Vérifier le panneau O2Switch** pour le format exact
2. **Utiliser FileZilla/Cyberduck** si SSH ne fonctionne pas
3. **Compiler sur le serveur** si Node.js est disponible

