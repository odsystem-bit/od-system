# Tracy Canonical Model

## Objectif

Le Canonical Model définit les objets fondamentaux utilisés dans toute la plateforme.

Tous les moteurs, tous les agents, tous les domaines et tous les outils utilisent ces mêmes modèles.

Aucun composant ne crée son propre format.

---

# Principe

Un objet = une seule définition.

Une seule vérité.

Une seule structure.

---

# Les objets fondamentaux

Tracy est construite autour de dix objets universels.

- User
- Organization
- Goal
- Mission
- Workflow
- Task
- Event
- Decision
- ToolCall
- Memory

Ces objets sont indépendants de la technologie.

Ils représentent uniquement le métier.

---

# User

Représente une personne.

Exemples :

- propriétaire
- employé
- client
- administrateur

Attributs principaux :

- id
- organisation
- rôle
- permissions
- préférences
- statut

---

# Organization

Représente une entreprise.

Contient :

- identité
- paramètres
- politiques
- objectifs
- membres
- abonnements

---

# Goal

Objectif stratégique.

Exemples :

- augmenter les ventes
- réduire les délais
- améliorer la satisfaction

Possède :

- KPI
- priorité
- échéance
- progression

---

# Mission

Travail à accomplir.

Possède :

- objectif
- état
- priorité
- workflow
- historique
- propriétaire

Une Mission peut créer d'autres Missions.

---

# Workflow

Plan d'exécution.

Possède :

- étapes
- dépendances
- validations
- reprise
- durée

---

# Task

Plus petite unité de travail.

Exemple :

Créer un produit.

Envoyer un message.

Scanner un site.

Une Task utilise toujours un Tool.

---

# Decision

Résultat du Decision Engine.

Contient :

- contexte
- options étudiées
- choix retenu
- justification
- niveau de confiance

Chaque décision est traçable.

---

# ToolCall

Invocation d'un Tool.

Possède :

- Tool
- paramètres
- résultat
- durée
- coût
- erreurs

---

# Event

Tout changement significatif.

Exemples :

MissionCreated

OrderPaid

WorkflowCompleted

ToolFailed

StockLow

Les Events sont immuables.

---

# Memory

Connaissance persistante.

Types :

- entreprise
- client
- conversation
- apprentissage
- préférences

Chaque mémoire possède :

- une source
- une confiance
- une date
- une durée de vie

---

# Relations

Organization

↓

Users

↓

Goals

↓

Missions

↓

Workflows

↓

Tasks

↓

ToolCalls

↓

Events

↓

Memory

Les Events et la Memory peuvent être générés à chaque niveau.

---

# Identifiants

Chaque objet possède :

- id unique
- version
- date de création
- date de modification
- auteur

---

# Versionnement

Tous les objets sont versionnés.

Une modification crée une nouvelle version.

L'historique reste disponible.

---

# États

Chaque objet possède un cycle de vie défini.

Les transitions sont décrites dans le State Machine.

---

# Traçabilité

Chaque objet est lié à :

- son créateur
- son historique
- les événements associés
- les décisions associées
- les workflows associés

---

# Objectif final

Garantir un langage universel partagé par toute la plateforme Tracy afin d'assurer la cohérence, la maintenabilité et l'évolutivité du système.