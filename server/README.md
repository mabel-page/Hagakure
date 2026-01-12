# HKC Gallery Backend

Petit backend Node.js pour gérer les albums et l'envoi de photos (drag & drop) + génération de vignettes.

## Installation

```bash
cd server
npm install
cp .env.example .env       # puis éditez utilisateur/mot de passe
node server.js             # ou: npm start
```

Le site statique (vos pages existantes) doit être à la **racine du projet** (un niveau au-dessus de `server/`).  
Le backend sert les fichiers statiques de cette racine et expose l'API sous `/api/...`.

## Config (.env)

```
PORT=3000
ADMIN_USER=admin
ADMIN_PASS=admin123
MAX_FILE_MB=15
```

## API

- `GET /api/albums` → liste des albums
- `GET /api/albums/:slug` → liste des images d'un album (full + thumb)
- `POST /api/upload` (protégé basic-auth) → upload de fichiers
  - form-data:
    - `albumName` (texte) — nom d'album (créé si n'existe pas)
    - `files` (plusieurs fichiers)

## Arborescence générée

```
uploads/
  mon-album/
    _thumbs/
      image1.jpg
    image1.jpg
    image2.jpg
```
