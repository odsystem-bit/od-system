# WhatsApp Bot - Serveur Meta Cloud API

Serveur Node.js/TypeScript pour recevoir et envoyer des messages via l'API WhatsApp de Meta (Cloud API).

## Installation locale

```bash
npm install
cp .env.example .env
# Éditez .env avec vos credentials
npm run dev
```

Le serveur démarrera sur le port configuré (3000 par défaut).

## Déploiement en production (cPanel/Passenger)

### 1. Build

```bash
npm run build
```

Cela compile le TypeScript vers le dossier `dist/`.

### 2. Configuration cPanel

1. Dans cPanel, allez dans "Setup Node.js App"
2. Créez une nouvelle application avec :
   - **Node.js version** : 18 ou supérieur
   - **Application mode** : Production
   - **Application root** : chemin vers ce dossier
   - **Application URL** : bot.odsysteme.tech
   - **Application startup file** : app.js
3. Définissez les variables d'environnement dans l'interface cPanel (copiez depuis .env)

### 3. Redémarrage

Après chaque modification, rebuild et redémarrez l'application via cPanel.

## Structure du projet

- `src/index.ts` : Point d'entrée Express
- `src/routes/webhook.ts` : Routes GET/POST pour le webhook Meta
- `src/db/connection.ts` : Pool de connexion MySQL
- `src/db/schema.sql` : Schéma de la base de données
- `src/services/whatsapp.ts` : Service d'envoi de messages
- `app.js` : Fichier de démarrage pour Passenger (pointe vers dist/index.js)

## Variables d'environnement

- `WHATSAPP_TOKEN` : Token d'accès Meta Cloud API
- `WHATSAPP_PHONE_NUMBER_ID` : ID du numéro WhatsApp
- `WHATSAPP_VERIFY_TOKEN` : Token de vérification webhook
- `OPENAI_API_KEY` : Clé OpenAI (pour futures fonctionnalités)
- `MANTOTA_API_URL` : URL de l'API MANTOTA
- `MANTOTA_API_SECRET` : Secret API MANTOTA
- `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` : Connexion MySQL
- `PORT` : Port d'écoute (3000 par défaut)
