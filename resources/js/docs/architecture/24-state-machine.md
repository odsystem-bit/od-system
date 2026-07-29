# Tracy State Machine

## Objectif

Le State Machine définit tous les états autorisés des objets métier de Tracy ainsi que les transitions possibles.

Aucun objet ne peut changer d'état en dehors de ces règles.

Le State Machine garantit la cohérence de toute la plateforme.

---

# Principe

Etat actuel

↓

Evénement

↓

Validation

↓

Nouvel état

Toutes les transitions sont contrôlées.

---

# Les objets concernés

- Conversation
- Goal
- Mission
- Workflow
- Task
- ToolCall

Chaque objet possède sa propre machine à états.

---

# Conversation

CREATED

↓

ACTIVE

↓

WAITING_USER

↓

ACTIVE

↓

WAITING_SYSTEM

↓

ACTIVE

↓

RESOLVED

↓

CLOSED

↓

ARCHIVED

Transitions interdites :

ARCHIVED → ACTIVE

CLOSED → ACTIVE

---

# Mission

NEW

↓

PLANNING

↓

READY

↓

RUNNING

↓

WAITING

↓

RUNNING

↓

VALIDATING

↓

COMPLETED

Autres états :

FAILED

PAUSED

CANCELLED

Transitions interdites :

COMPLETED → RUNNING

FAILED → COMPLETED

CANCELLED → RUNNING

---

# Workflow

CREATED

↓

READY

↓

RUNNING

↓

VALIDATING

↓

COMPLETED

Autres états :

FAILED

WAITING

PAUSED

CANCELLED

---

# Task

PENDING

↓

READY

↓

RUNNING

↓

SUCCESS

Autres états :

FAILED

WAITING

SKIPPED

CANCELLED

---

# ToolCall

REQUESTED

↓

STARTED

↓

COMPLETED

Autres états :

FAILED

TIMEOUT

CANCELLED

RETRYING

---

# Goal

DRAFT

↓

ACTIVE

↓

ON_TRACK

↓

COMPLETED

Autres états :

AT_RISK

PAUSED

FAILED

ARCHIVED

---

# Règles générales

Chaque transition :

- est validée
- est journalisée
- publie un Event
- peut être refusée

---

# Transition interdite

Si une transition est interdite :

Le changement est rejeté.

Un événement est enregistré.

Une erreur est générée.

Aucune modification n'est appliquée.

---

# Reprise

Les états WAITING et PAUSED permettent une reprise.

La reprise se fait toujours depuis le dernier état valide.

---

# Historique

Chaque changement d'état conserve :

- ancien état
- nouvel état
- date
- auteur
- moteur responsable
- justification

L'historique est immuable.

---

# Objectif final

Garantir que tous les objets évoluent selon des cycles de vie prévisibles, contrôlés et cohérents.