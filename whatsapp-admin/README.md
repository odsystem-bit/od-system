# WhatsApp Admin Dashboard

Dashboard admin privé pour Tracy WhatsApp Bot.

## Installation

1. Copier le fichier `.env.example` vers `.env` et configurer les variables d'environnement:
   ```bash
   cp .env.example .env
   ```

2. Configurer les variables dans `.env`:
   - `DB_HOST`: Hôte de la base de données MySQL
   - `DB_USER`: Utilisateur MySQL
   - `DB_PASSWORD`: Mot de passe MySQL
   - `DB_NAME`: Nom de la base de données (doit être la même que whatsapp-bot)
   - `SESSION_SECRET`: Secret pour les sessions (chaîne aléatoire longue)
   - `ADMIN_PORT`: Port du dashboard (défaut: 4000)

3. Installer les dépendances:
   ```bash
   npm install
   ```

## Créer un compte admin

Utiliser le script pour créer un compte admin:
```bash
npm run create-admin <email> <password>
```

Exemple:
```bash
npm run create-admin tracy@example.com monMotDePasseSecure123
```

## Démarrer le serveur

En développement:
```bash
npm run dev
```

En production:
```bash
npm start
```

Le dashboard sera accessible sur `http://localhost:4000`

## Fonctionnalités

- **Dashboard**: Statistiques en temps réel, alertes d'erreurs, graphiques de nouveaux clients
- **Clients**: Liste des clients avec filtres, détails client, gestion de statut, changement de plan
- **Logs**: Visualisation des logs système avec filtres et mode en direct
- **Plans**: Gestion des tarifs et limites des abonnements

## Sécurité

- Authentification par session avec bcrypt (12 rounds)
- Rate limiting sur login (5 tentatives par 10 minutes)
- Header X-Robots-Tag noindex,nofollow sur toutes les pages
- Sessions stockées en base MySQL
- Cookie sécurisé en production

## Structure du projet

```
whatsapp-admin/
├── src/
│   ├── db/
│   │   └── connection.ts      # Connexion MySQL
│   ├── middleware/
│   │   └── auth.ts            # Middleware d'authentification
│   ├── routes/
│   │   ├── auth.ts            # Routes login/logout
│   │   ├── dashboard.ts       # Route dashboard
│   │   ├── clients.ts         # Routes clients
│   │   ├── logs.ts            # Routes logs
│   │   └── plans.ts           # Routes plans
│   ├── scripts/
│   │   └── create-admin.ts    # Script création admin
│   ├── views/
│   │   ├── layout.ejs         # Layout principal
│   │   ├── login.ejs          # Page login
│   │   ├── dashboard.ejs      # Page dashboard
│   │   ├── clients.ejs        # Page liste clients
│   │   ├── client-detail.ejs # Page détail client
│   │   ├── logs.ejs           # Page logs
│   │   └── plans.ejs          # Page plans
│   └── index.ts               # Point d'entrée
├── app.js                     # Entry point pour Passenger/cPanel
├── package.json
├── tsconfig.json
└── .env.example
```

## Déploiement

Le fichier `app.js` est configuré pour fonctionner avec Passenger/cPanel. Le serveur démarre sur le port défini par `ADMIN_PORT` (4000 par défaut).
