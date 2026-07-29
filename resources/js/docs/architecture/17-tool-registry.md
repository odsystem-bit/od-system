# Tracy Tool Registry

## Objectif

Le Tool Registry est le catalogue officiel de tous les outils disponibles dans Tracy.

Il permet aux moteurs et aux agents de découvrir, sélectionner et utiliser les outils sans connaître leur implémentation.

Les outils sont déclaratifs.

Les agents demandent une capacité.

Le Registry fournit le meilleur outil disponible.

---

# Principe

Mission

↓

Workflow

↓

Recherche d'une capacité

↓

Tool Registry

↓

Sélection de l'outil

↓

Exécution

↓

Résultat

---

# Définition d'un Tool

Chaque Tool possède :

- un identifiant unique
- un nom
- une description
- une version
- un domaine
- une catégorie
- une capacité
- un propriétaire
- un niveau de criticité

---

# Capacités

Une capacité décrit ce qu'un outil sait faire.

Exemples :

SEND_MESSAGE

CREATE_PRODUCT

UPDATE_PRODUCT

DELETE_PRODUCT

CREATE_ORDER

IMPORT_CATALOG

SCAN_WEBSITE

GENERATE_IMAGE

CREATE_CAMPAIGN

SEARCH_CUSTOMER

PROCESS_PAYMENT

REFUND_PAYMENT

GENERATE_REPORT

UPLOAD_FILE

DOWNLOAD_FILE

TRANSCRIBE_AUDIO

SUMMARIZE_DOCUMENT

---

# Permissions

Chaque Tool déclare :

- rôles autorisés
- domaines autorisés
- agents autorisés
- niveau de sécurité

Un outil ne peut jamais être exécuté sans validation des permissions.

---

# Paramètres

Chaque Tool définit :

Entrées :

- paramètres obligatoires
- paramètres optionnels
- contraintes de validation

Sorties :

- structure des données
- erreurs possibles
- événements générés

---

# Métadonnées

Chaque Tool déclare :

- temps moyen d'exécution
- coût estimé
- timeout
- nombre maximal de tentatives
- possibilité d'exécution parallèle
- caractère idempotent
- nécessité d'une confirmation utilisateur

---

# États

REGISTERED

ACTIVE

DEPRECATED

DISABLED

ARCHIVED

---

# Sélection

Lorsqu'une capacité est demandée :

Le Tool Registry :

1. recherche tous les outils compatibles

2. filtre selon les permissions

3. filtre selon le contexte

4. élimine les outils indisponibles

5. classe les candidats

6. retourne le meilleur outil

---

# Exemple

Capacité :

SEND_MESSAGE

Outils disponibles :

WhatsApp Cloud

WhatsApp Business API

SMS Gateway

Email

Selon le contexte :

Le Registry retourne :

WhatsApp Cloud

---

# Versionnement

Plusieurs versions d'un même Tool peuvent coexister.

Exemple :

Import Website v1

Import Website v2

Le Registry choisit la version active.

---

# Événements

Chaque Tool publie des événements :

TOOL_STARTED

TOOL_PROGRESS

TOOL_COMPLETED

TOOL_FAILED

Ces événements sont utilisés par :

- Workflow Engine
- Observation Engine
- Analytics Engine

---

# Santé

Chaque Tool expose :

- disponibilité
- temps de réponse
- taux d'erreur
- dernière exécution
- version

Un outil dégradé peut être automatiquement écarté.

---

# Règles

Un Tool :

- ne contient aucune logique métier
- n'accède jamais directement à la base de données
- exécute uniquement une capacité
- renvoie toujours un résultat structuré
- journalise chaque exécution

---

# Objectif final

Découpler complètement les moteurs de Tracy des implémentations techniques afin de rendre la plateforme extensible, maintenable et indépendante des fournisseurs externes.