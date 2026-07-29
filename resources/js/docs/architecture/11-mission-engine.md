# Tracy Mission Engine

## Objectif

Le Mission Engine transforme une demande utilisateur en une mission.

Une mission représente un objectif à atteindre.

Tracy n'a pas pour objectif de répondre.

Elle a pour objectif d'accomplir des missions.

---

# Définition

Une mission possède :

- un objectif
- un propriétaire
- un état
- une priorité
- un plan
- une liste de tâches
- une date de création
- un historique

---

# Cycle de vie

Création

↓

Analyse

↓

Planification

↓

Exécution

↓

Vérification

↓

Validation

↓

Terminé

---

# Etats possibles

NEW

Mission créée.

PLANNING

Construction du plan.

WAITING

En attente d'une information.

RUNNING

Mission en cours.

PAUSED

Mission suspendue.

FAILED

Mission échouée.

COMPLETED

Mission terminée.

CANCELLED

Mission annulée.

---

# Priorité

LOW

NORMAL

HIGH

URGENT

CRITICAL

---

# Types de mission

Commerce

Paiement

Support

Marketing

Administration

Analyse

Import

Communication

CRM

Maintenance

---

# Exemple

Mission :

Créer une boutique.

Objectif :

Boutique disponible avant la fin de la conversation.

Sous-tâches :

Demander le nom

Demander le secteur

Créer la boutique

Créer le propriétaire

Créer les paramètres

Créer les catégories

Créer les messages par défaut

Créer les préférences

Notifier

Terminer

---

Mission :

Créer une commande

Sous-tâches :

Vérifier le client

Vérifier le stock

Créer la commande

Créer le paiement

Créer le reçu

Notifier

---

Mission :

Importer un catalogue

Sous-tâches :

Scanner

Détecter les produits

Télécharger les images

Importer

Créer les catégories

Créer les variantes

Créer le rapport

---

# Mission interrompue

Si une mission est interrompue :

Sauvegarder l'état

Sauvegarder les tâches terminées

Sauvegarder les erreurs

Reprendre plus tard

---

# Historique

Toutes les missions possèdent un historique complet.

Date

Action

Résultat

Erreur éventuelle

---

# Une mission peut créer d'autres missions

Exemple :

Créer une boutique

↓

Créer automatiquement

Mission :

Créer le catalogue

↓

Mission :

Créer les catégories

↓

Mission :

Créer les paramètres

---

# Principe fondamental

Une mission n'appelle jamais directement la base de données.

Elle demande aux domaines métier d'exécuter les actions.

Les domaines utilisent ensuite les Services.

Les Services utilisent les Repositories.

Les Repositories accèdent à la base de données.