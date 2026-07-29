# Tracy Event Model

## Objectif

L'Event Model définit le langage universel de communication entre tous les composants de Tracy.

Aucun moteur ne dépend directement d'un autre.

Ils échangent uniquement des événements.

---

# Principe

Événement

↓

Publication

↓

Event Bus

↓

Abonnés

↓

Traitement

↓

Nouveaux événements

---

# Définition

Un événement représente un fait déjà survenu.

Un événement est immuable.

Il ne peut jamais être modifié.

---

# Structure

Chaque Event possède :

- id

- type

- version

- source

- date

- auteur

- contexte

- données

- corrélation

- causalité

---

# Grandes familles

## Conversation

ConversationStarted

MessageReceived

ConversationPaused

ConversationClosed

---

## Mission

MissionCreated

MissionStarted

MissionCompleted

MissionFailed

MissionCancelled

---

## Workflow

WorkflowCreated

WorkflowStarted

WorkflowCompleted

WorkflowFailed

StepStarted

StepCompleted

---

## Tool

ToolCalled

ToolCompleted

ToolFailed

---

## Commerce

ProductCreated

OrderCreated

OrderPaid

StockUpdated

PromotionCreated

---

## Paiement

PaymentInitiated

PaymentSucceeded

PaymentFailed

RefundRequested

RefundCompleted

---

## IA

ReasoningCompleted

DecisionMade

StrategySelected

GoalUpdated

LearningRecorded

---

## Sécurité

PermissionDenied

AuthenticationSucceeded

AuthenticationFailed

SuspiciousActivityDetected

---

# Corrélation

Tous les événements liés à une même mission partagent un Correlation ID.

Cela permet de reconstruire toute l'histoire d'une action.

---

# Causalité

Chaque événement connaît son origine.

Exemple :

MessageReceived

↓

MissionCreated

↓

WorkflowStarted

↓

ToolCalled

↓

ToolCompleted

↓

MissionCompleted

---

# Garantie

Les événements sont :

- immuables
- horodatés
- versionnés
- traçables
- persistants

---

# Règles

Un Event :

- ne contient aucune logique métier

- ne déclenche aucune action directement

- décrit uniquement un fait

Les moteurs décident ensuite comment réagir.

---

# Objectif final

Créer un langage événementiel unique permettant à tous les composants de Tracy de collaborer de manière découplée, fiable et évolutive.