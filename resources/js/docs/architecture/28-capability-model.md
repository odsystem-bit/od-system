# Tracy Capability Model

## Objectif

Une Capability représente une compétence métier que Tracy maîtrise.

Elle est indépendante :

- des technologies
- des outils
- des interfaces
- des fournisseurs

Elle décrit uniquement ce que Tracy sait faire.

---

# Architecture

Domain

↓

Capability

↓

Service

↓

Workflow

↓

Tool

---

# Exemple

Domain

Commerce

↓

Capability

Gestion du catalogue

↓

Services

Créer un produit

Modifier un produit

Supprimer un produit

Importer un catalogue

↓

Workflow

Créer un produit complet

↓

Tools

OpenAI

Cloudinary

Storage

Database

---

# Une Capability possède

- id
- nom
- domaine
- description
- services
- workflows
- événements
- permissions
- politiques

---

# Une Capability ne connaît jamais

- Laravel

- PostgreSQL

- Redis

- WhatsApp

- OpenAI

- Docker

Elle reste purement métier.

---

# Domaines

## Commerce

Capabilities

Catalogue

Stocks

Commandes

Promotions

Livraison

Facturation

---

## Marketing

Capabilities

Campagnes

Audience

Segmentation

Promotion

Relances

Smart Links

---

## Communication

Capabilities

WhatsApp

SMS

Email

Notifications

Templates

Pièces jointes

---

## CRM

Capabilities

Clients

Prospects

Historique

Préférences

Fidélité

---

## Paiement

Capabilities

Wallet

Escrow

Transactions

Remboursements

Factures

Abonnements

---

## IA

Capabilities

Conversation

Mémoire

Décision

Stratégie

Apprentissage

Observation

---

# Règles

Une Capability :

- possède plusieurs Services
- peut publier des Events
- peut déclencher des Missions
- ne dépend jamais d'une autre Capability

La communication se fait par contrats ou événements.

---

# Objectif final

Découper chaque domaine en compétences métier indépendantes afin de rendre Tracy plus simple à maintenir, tester et faire évoluer.