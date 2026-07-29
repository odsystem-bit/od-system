# OD Système Ecosystem Architecture

## Objectif

OD Système est une plateforme regroupant plusieurs applications métier.

Chaque application est indépendante.

Toutes partagent les mêmes fondations techniques et certains modules métier.

---

# Les applications

apps/

    tracy/

    mantota/

    odisy/

    monparc/

    nnaki/

    admin/

Chaque application possède :

- son interface
- ses routes
- ses permissions
- sa configuration

Mais aucune ne réimplémente les services communs.

---

# Les packages métier

packages/

    commerce/

    crm/

    communication/

    payments/

    analytics/

    marketing/

    ai/

    workflow/

    goals/

Ces packages peuvent être utilisés par plusieurs applications.

---

# Les services de plateforme

platform/

    ai-runtime/

    event-bus/

    scheduler/

    queue/

    notification/

    storage/

    security/

    tenant/

    plugins/

    observability/

Ils sont communs à tout l'écosystème.

---

# Communication

Les applications ne s'appellent jamais directement.

Elles communiquent via :

- Events
- Contracts
- APIs publiques

---

# Exemple

Mantota

↓

OrderCreated

↓

Event Bus

↓

Tracy

↓

Analyse de la commande

↓

Suggestion commerciale

---

# Multi-tenant

Chaque entreprise possède :

- ses utilisateurs
- ses données
- ses permissions
- ses configurations

Toutes les données restent isolées.

---

# Authentification

Un utilisateur possède un seul compte.

Ce compte peut accéder à plusieurs applications selon ses droits.

---

# Objectif final

Construire un écosystème cohérent où chaque produit évolue indépendamment tout en partageant les mêmes fondations.