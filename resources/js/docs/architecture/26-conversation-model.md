# Tracy Conversation Model

## Objectif

La Conversation est l'unité de travail principale de Tracy.

Toute interaction entre un utilisateur et Tracy est représentée par une Conversation.

Une Conversation possède son propre contexte, sa mémoire, ses missions et son historique.

---

# Principe

Utilisateur

↓

Conversation

↓

Missions

↓

Workflows

↓

Tasks

↓

Tool Calls

↓

Résultats

---

# Définition

Une Conversation représente un échange continu.

Elle peut durer :

- quelques secondes
- plusieurs jours
- plusieurs mois

Elle reste le point d'entrée de toutes les décisions.

---

# Types

## Conversation Utilisateur

WhatsApp

Messenger

Instagram

Telegram

Web Chat

Application Mobile

---

## Conversation Système

Déclenchée automatiquement.

Exemples :

Stock faible

Paiement reçu

Campagne terminée

Erreur critique

---

## Conversation Interne

Communication entre moteurs.

Invisible pour l'utilisateur.

---

# Participants

Une Conversation possède :

- un propriétaire

- un ou plusieurs utilisateurs

- Tracy

- éventuellement plusieurs agents spécialisés

---

# Contexte

Chaque Conversation conserve :

- langue

- fuseau horaire

- canal

- entreprise

- permissions

- préférences

- objectifs actifs

---

# Mémoire

Une Conversation possède sa propre mémoire.

Elle contient :

- informations déjà demandées

- réponses précédentes

- décisions prises

- fichiers reçus

- liens

- préférences découvertes

---

# Missions

Une Conversation peut créer plusieurs missions.

Exemple :

Conversation

↓

Créer boutique

↓

Importer catalogue

↓

Configurer paiements

↓

Créer campagne

Toutes restent rattachées à la même Conversation.

---

# Décisions

Chaque décision prise pendant une Conversation est historisée.

Exemple :

Question posée

↓

Décision

↓

Justification

↓

Résultat

---

# Événements

Une Conversation publie des événements.

Exemples :

ConversationStarted

MessageReceived

MissionCreated

WorkflowCompleted

ConversationPaused

ConversationClosed

---

# États

CREATED

ACTIVE

WAITING_USER

WAITING_SYSTEM

PAUSED

RESOLVED

CLOSED

ARCHIVED

---

# Reprise

Une Conversation peut être reprise plusieurs semaines plus tard.

Tracy recharge :

- le contexte

- les missions

- la mémoire

- les objectifs

- les décisions

avant de répondre.

---

# Clôture

Une Conversation est clôturée lorsque :

- toutes les missions sont terminées

- aucun objectif n'est actif

- aucun message n'est attendu

La mémoire reste disponible.

---

# Règles

Une Conversation :

- ne contient aucune logique métier

- ne prend aucune décision

- orchestre uniquement le contexte de travail

Tous les moteurs utilisent la Conversation comme référence commune.

---

# Objectif final

Faire de chaque Conversation un espace de travail persistant permettant à Tracy de travailler de manière continue, cohérente et contextuelle, quel que soit le canal utilisé.