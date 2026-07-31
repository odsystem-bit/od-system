# Domain Dependencies

## Objectif

Ce document définit les dépendances autorisées entre les domaines métier de Tracy.

Les dépendances sont à sens unique.

Les dépendances circulaires sont strictement interdites.

---

# Règle générale

Un domaine ne peut dépendre que d'un domaine situé en dessous de lui.

Il ne peut jamais dépendre d'un domaine qui dépend déjà de lui.

---

# Dépendances

## Identity

Dépend de :

Aucun

Utilisé par :

- AI
- Catalog
- Orders
- Payments
- Marketing
- Communication
- Support
- Analytics

---

## AI

Dépend de :

- Identity

Utilisé par :

- Marketing
- Communication
- Support

---

## Catalog

Dépend de :

- Identity

Utilisé par :

- Orders
- Marketing

---

## Orders

Dépend de :

- Identity
- Catalog

Utilisé par :

- Payments
- Support
- Analytics

---

## Payments

Dépend de :

- Identity
- Orders

Utilisé par :

- Marketing
- Analytics

---

## Marketing

Dépend de :

- Identity
- Catalog
- Payments
- AI

Utilisé par :

- Analytics

---

## Communication

Dépend de :

- Identity
- AI

Utilisé par :

- Tous les domaines

---

## Support

Dépend de :

- Identity
- Orders
- Payments
- Communication

Utilisé par :

- Analytics

---

## Analytics

Dépend de :

- Identity
- Catalog
- Orders
- Payments
- Marketing
- Communication
- Support
- AI

Utilisé par :

Aucun

---

# Dépendances interdites

Les relations suivantes sont interdites :

- Identity → Payments
- Identity → Orders
- Identity → Marketing
- Identity → AI
- Catalog → Payments
- Payments → Catalog
- Payments → AI
- Orders → Marketing
- Analytics → Identity
- Analytics → Payments
- Analytics → Orders

---

# Communication entre domaines

Les domaines communiquent uniquement par :

- Interfaces
- Cas d'utilisation (Application Layer)
- Événements métier (Domain Events)

Les accès directs aux entités ou aux bases de données d'un autre domaine sont interdits.

---

# Principe

Chaque domaine doit pouvoir :

- être développé indépendamment ;
- être testé indépendamment ;
- être déployé indépendamment à l'avenir ;
- évoluer sans casser les autres domaines.