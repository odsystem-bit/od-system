# Context Map

## Objectif

Ce document définit les frontières des domaines métier de Tracy ainsi que leurs dépendances.

Chaque domaine possède sa propre logique métier.

Un domaine ne peut jamais accéder directement aux données internes d'un autre domaine.

Toutes les communications passent par des interfaces, des événements ou des cas d'utilisation.

---

# Domaines

## Identity

Responsabilités :

- Authentification
- Utilisateurs
- Organisations
- Workspaces
- Teams
- Rôles
- Permissions

Dépend de :

Aucun

Utilisé par :

- AI
- Payments
- Catalog
- Orders
- Marketing
- Communication
- Support
- Analytics

---

## AI

Responsabilités :

- Conversation
- Memory
- Knowledge
- Decision
- Reasoning
- Mission
- Goal
- Workflow
- Agent

Dépend de :

- Identity

Utilisé par :

- Tous les domaines

---

## Catalog

Responsabilités :

- Produits
- Services
- Catégories
- Prix
- Stock

Dépend de :

- Identity

Utilisé par :

- Orders
- Marketing

---

## Orders

Responsabilités :

- Commandes
- Panier
- Livraison
- Facturation

Dépend de :

- Identity
- Catalog

Utilisé par :

- Payments
- Support

---

## Payments

Responsabilités :

- Wallet
- Transactions
- Dépôts
- Retraits
- Escrow
- Gateway

Dépend de :

- Identity
- Orders

Utilisé par :

- Marketing
- Support

---

## Marketing

Responsabilités :

- Campagnes
- Smart Links
- Influence
- Statistiques marketing

Dépend de :

- Identity
- Catalog
- Payments

---

## Communication

Responsabilités :

- WhatsApp
- Email
- SMS
- Push

Dépend de :

- Identity

---

## Support

Responsabilités :

- Tickets
- Litiges
- Réclamations
- Chat

Dépend de :

- Identity
- Orders
- Payments

---

## Analytics

Responsabilités :

- Rapports
- KPIs
- Dashboards

Dépend de :

- Tous les domaines

---

# Règles

- Identity ne dépend de personne.
- Aucun domaine ne peut accéder directement à la base de données d'un autre domaine.
- Toute communication passe par des interfaces ou des événements.
- Les dépendances circulaires sont interdites.
- Chaque domaine doit pouvoir évoluer indépendamment.