# OD System Monorepo

## Objectif

Le dépôt `od-system` contient tout l'écosystème.

Chaque dossier possède une responsabilité claire.

Aucun code métier ne doit être placé à la racine.

---

od-system/

    apps/
    packages/
    platform/
    shared/
    infrastructure/
    docker/
    scripts/
    docs/
    tests/

---

# apps/

Contient les applications.

apps/

    tracy/

    mantota/

    odisy/

    monparc/

    nnaki/

    admin/

Une application :

- possède son interface
- possède ses routes
- possède sa configuration

Elle n'implémente jamais la logique métier.

---

# packages/

Contient les domaines métier.

packages/

    commerce/

    crm/

    communication/

    payment/

    workflow/

    goals/

    knowledge/

    analytics/

    identity/

    ai/

Chaque package est indépendant.

---

# platform/

Services techniques.

platform/

    ai-runtime/

    event-bus/

    scheduler/

    notification/

    queue/

    cache/

    search/

    storage/

    security/

    tenancy/

    feature-flags/

    plugins/

---

# shared/

Code partagé.

shared/

    contracts/

    events/

    exceptions/

    helpers/

    enums/

    value-objects/

---

# infrastructure/

Configuration technique.

infrastructure/

    nginx/

    postgres/

    redis/

    minio/

---

# docker/

docker/

    php/

    nginx/

    postgres/

    redis/

---

# scripts/

scripts/

    install/

    backup/

    migrate/

    deploy/

---

# docs/

Toute la documentation.

Vision

Architecture

ADR

API

Roadmap

Business

---

# tests/

tests/

    Unit/

    Integration/

    Architecture/

    Performance/

    E2E/

---

# Objectif

Permettre à n'importe quel développeur de comprendre le projet en moins de trente minutes.