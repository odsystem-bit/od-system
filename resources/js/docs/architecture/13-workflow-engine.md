# Tracy Workflow Engine

## Objectif

Le Workflow Engine orchestre l'exécution des missions.

Il transforme un plan en un ensemble d'étapes exécutables.

Il gère :

- l'ordre d'exécution
- les dépendances
- les validations
- les erreurs
- les reprises
- les workflows parallèles

---

# Définition

Un workflow est une suite d'étapes permettant d'atteindre un objectif métier.

Chaque workflow est composé de :

- un identifiant
- une mission
- un état
- des étapes
- des dépendances
- un historique

---

# Cycle de vie

Création

↓

Préparation

↓

Exécution

↓

Validation

↓

Terminé

---

# États

CREATED

READY

RUNNING

WAITING

PAUSED

FAILED

COMPLETED

CANCELLED

---

# Structure d'une étape

Chaque étape possède :

- un identifiant
- un nom
- un domaine métier
- un Tool
- des paramètres
- des prérequis
- un résultat attendu
- un état

---

# Dépendances

Une étape peut dépendre d'une ou plusieurs autres.

Exemple :

Créer une commande

↓

Créer le paiement

↓

Créer la facture

↓

Notifier le client

Une étape ne démarre jamais tant que ses prérequis ne sont pas satisfaits.

---

# Exécution parallèle

Certaines étapes peuvent être exécutées simultanément.

Exemple :

Importer les images

||

Créer les catégories

||

Télécharger les fiches produits

Une fois terminées :

↓

Créer les produits

---

# Validation

Après chaque étape :

- résultat conforme ?
- données cohérentes ?
- aucune erreur critique ?

Si non :

arrêt du workflow.

---

# Gestion des erreurs

Si une étape échoue :

- enregistrer l'erreur
- identifier la cause
- tenter une récupération si possible
- sinon mettre le workflow en attente ou en échec

Aucune étape suivante n'est exécutée si la dépendance échoue.

---

# Reprise

Chaque workflow est persisté.

En cas de coupure :

- reprendre à la dernière étape validée
- ne jamais recommencer inutilement les étapes déjà terminées

---

# Rollback

Certaines actions sont réversibles.

Exemple :

Créer une promotion

↓

Erreur

↓

Supprimer la promotion

↓

Restaurer l'état précédent

Les opérations irréversibles doivent être confirmées avant leur exécution.

---

# Historique

Chaque exécution conserve :

- date
- étape
- durée
- outil utilisé
- résultat
- erreur éventuelle

---

# Exemple

Mission :

Créer une boutique complète.

Workflow :

1. Vérifier les permissions

2. Créer la boutique

3. Créer les catégories

4. Créer les paramètres

5. Créer le catalogue

6. Configurer les paiements

7. Vérifier l'ensemble

8. Envoyer le rapport

Mission terminée.