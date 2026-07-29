# Tracy Platform Architecture

## Objectif

La Platform fournit tous les services techniques communs utilisés par les domaines métier.

Les domaines ne connaissent jamais directement les technologies utilisées.

Ils utilisent uniquement les services de la plateforme.

---

# Les quatre couches

Applications

↓

Business Packages

↓

Platform

↓

Infrastructure

---

# Applications

Exemples :

- Tracy
- Mantota
- Admin
- API publique

Les applications orchestrent les fonctionnalités.

Elles ne contiennent presque aucune logique métier.

---

# Business Packages

Ils contiennent toute la logique métier.

Exemples :

- Commerce
- CRM
- Marketing
- Paiement
- IA
- Communication
- Analytics

---

# Platform

La plateforme fournit des services techniques communs.

Elle ne connaît pas le métier.

Elle fournit uniquement des capacités techniques.

---

# Services de la Platform

## AI Runtime

Gestion des modèles IA

- OpenAI
- Claude
- Gemini
- Llama
- modèles locaux

---

## Event Bus

Publication

Souscription

Distribution

Historique

---

## Scheduler

Gestion des tâches planifiées.

---

## Queue

Gestion des files d'attente.

---

## Cache

Redis

Mémoire temporaire

---

## Storage

Documents

Images

Audio

Vidéo

---

## Security

Authentification

Autorisation

Secrets

Chiffrement

Audit

---

## Observability

Logs

Métriques

Tracing

Alertes

---

## Feature Flags

Activation des fonctionnalités.

Déploiement progressif.

---

## Configuration

Configuration centralisée.

---

## Notification Hub

SMS

Email

WhatsApp

Push

---

## Plugin Manager

Chargement dynamique des extensions.

---

## Tenant Manager

Gestion du multi-tenant.

---

## API Gateway

Point d'entrée unique.

---

# Infrastructure

La dernière couche.

Exemples :

Laravel

PostgreSQL

Redis

Docker

Nginx

Cloud

---

# Dépendances

Application

↓

Business

↓

Platform

↓

Infrastructure

Jamais l'inverse.

---

# Objectif final

Séparer totalement le métier des préoccupations techniques afin de rendre Tracy évolutive, portable et facilement maintenable.