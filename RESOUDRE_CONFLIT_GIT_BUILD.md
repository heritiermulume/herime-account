# Résoudre le conflit Git avec les fichiers de build

## Problème
Lors du `git pull`, vous obtenez l'erreur :
```
erreur : Vos modifications locales aux fichiers suivants seraient écrasées par la fusion :
        public/build/assets/app-DVlYwTs.css
```

## Solution

Les fichiers dans `public/build/assets/` sont générés par Vite et ne doivent pas être versionnés individuellement. Voici comment résoudre le conflit :

### Option 1 : Supprimer le fichier en conflit (Recommandé)

```bash
# Sur O2Switch
rm -f public/build/assets/app-DVlYwTs.css
git pull origin main
```

### Option 2 : Supprimer tous les assets et pull

```bash
# Sur O2Switch
rm -rf public/build/assets/*
git pull origin main
```

### Option 3 : Utiliser le script automatique

```bash
# Sur O2Switch
bash resoudre-conflit-pull.sh
```

## Après le pull

### Si Node.js est disponible sur O2Switch :
```bash
npm run build
```

### Si Node.js n'est PAS disponible sur O2Switch :

1. **Compiler localement** :
   ```bash
   # Sur votre machine locale
   npm run build
   ```

2. **Transférer les fichiers build** :

   **Option A - Via SCP** :
   ```bash
   # Depuis votre machine locale
   scp -r public/build muhe3594@persil.o2switch.fr:/home/muhe3594/public_html/herime-account/public/
   ```

   **Option B - Via FileZilla/Cyberduck** :
   - Connectez-vous à `persil.o2switch.fr`
   - Naviguez vers `/home/muhe3594/public_html/herime-account/public/`
   - Remplacez le dossier `build/` par celui de votre machine locale

## Prévention

Pour éviter ce problème à l'avenir, assurez-vous que `.gitignore` contient :
```
/public/build/assets/*
!/public/build/manifest.json
```

Les fichiers dans `public/build/assets/` changent à chaque build (hashs différents), donc ils ne doivent pas être versionnés individuellement.

