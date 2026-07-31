# Ubiquitous Language

## Objectif

Ce document définit le vocabulaire officiel de Tracy.

Chaque terme possède une seule signification dans tout le projet.

Aucun synonyme ne doit être utilisé dans le code, la documentation ou les échanges techniques.

---

# Organisation

## Organization

Une entreprise ou une personne morale utilisant Tracy.

Exemples :

- OD Système
- Africa Elite

---

## Workspace

Un espace de travail appartenant à une Organization.

Une Organization peut posséder plusieurs Workspaces.

Exemples :

- Mantota
- MonParc
- Tracy
- N'NAKI

---

## User

Une personne possédant un compte Tracy.

Un User peut appartenir à plusieurs Workspaces.

---

## Membership

Relation entre un User et un Workspace.

Cette relation définit également le rôle de l'utilisateur dans ce Workspace.

---

## Team

Groupe d'utilisateurs dans un Workspace.

Exemples :

- Marketing
- Support
- Finance
- Commercial

---

## Role

Ensemble de permissions attribuées à un utilisateur.

Exemples :

- Owner
- Admin
- Manager
- Agent
- Member

---

## Permission

Autorisation précise permettant d'exécuter une action.

Exemples :

- campaign.create
- campaign.update
- order.read
- payment.withdraw

---

# Commerce

## Catalog

Ensemble des produits et services disponibles.

---

## Product

Produit vendu sur la plateforme.

---

## Order

Commande passée par un client.

---

## Cart

Panier contenant les produits avant validation.

---

# Paiement

## Wallet

Portefeuille électronique d'un utilisateur.

---

## Transaction

Mouvement d'argent.

---

## Escrow

Montant temporairement bloqué jusqu'à validation de la transaction.

---

## Gateway

Service externe de paiement.

Exemple :

- Moneroo

---

# Marketing

## Campaign

Campagne promotionnelle.

---

## Creator

Utilisateur qui fait la promotion d'une campagne.

---

## Smart Link

Lien unique permettant de suivre les clics et les conversions.

---

# Intelligence Artificielle

## Agent

Assistant intelligent spécialisé.

---

## Conversation

Historique des échanges entre un utilisateur et un Agent.

---

## Memory

Informations mémorisées par l'IA.

---

## Knowledge

Base de connaissances utilisée par l'IA.

---

## Goal

Objectif donné à un Agent.

---

## Mission

Ensemble de Goals permettant d'accomplir une tâche.

---

## Workflow

Suite d'actions exécutées automatiquement.

---

# Support

## Ticket

Demande d'assistance.

---

## Dispute

Litige entre plusieurs parties.

---

# Règles

- Un terme possède une seule définition.
- Aucun synonyme n'est autorisé.
- Les noms utilisés dans le code doivent respecter ce document.
- Toute nouvelle notion métier doit être ajoutée ici avant son implémentation.