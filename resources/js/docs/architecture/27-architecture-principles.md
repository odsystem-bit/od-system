# Tracy Architecture Principles

## Objectif

Ce document définit les principes fondamentaux de l'architecture logicielle de Tracy.

Toutes les décisions techniques devront respecter ces principes.

Ces règles sont non négociables.

---

# Principe n°1

Le métier dirige la technique.

Jamais l'inverse.

Les besoins métier déterminent l'architecture.

Les frameworks ne déterminent jamais le métier.

---

# Principe n°2

Domain Driven Design (DDD)

Le système est organisé autour des domaines métier.

Exemples :

- Commerce
- Paiements
- IA
- Communication
- CRM
- Marketing

Chaque domaine possède :

- son langage
- ses modèles
- ses services
- ses règles

---

# Principe n°3

Modular Monolith

Tracy est une seule application.

Mais elle est découpée en modules indépendants.

Chaque module possède :

- son API interne
- sa logique métier
- ses événements
- son stockage (si nécessaire)

Un module ne peut jamais accéder directement au code d'un autre.

---

# Principe n°4

Event Driven

Les modules communiquent principalement grâce aux événements.

Ils évitent les dépendances directes.

---

# Principe n°5

Dependency Inversion

Le métier ne dépend jamais :

- de Laravel
- de PostgreSQL
- de Redis
- d'OpenAI
- de WhatsApp

Le métier dépend uniquement d'interfaces.

---

# Principe n°6

Ports & Adapters

Les technologies sont des adaptateurs.

Le cœur métier reste indépendant.

---

# Principe n°7

Single Responsibility

Chaque composant possède une responsabilité unique.

---

# Principe n°8

Open / Closed

Le système est ouvert à l'extension.

Fermé à la modification.

Ajouter un nouveau fournisseur de paiement ne doit pas casser le reste du système.

---

# Principe n°9

Observabilité

Toutes les actions importantes doivent être :

- traçables
- mesurables
- auditables

---

# Principe n°10

Evolution

Tout module doit pouvoir évoluer indépendamment.

Le remplacement d'un composant technique ne doit pas impacter le métier.

---

# Objectif final

Construire une architecture durable, évolutive et indépendante des technologies.