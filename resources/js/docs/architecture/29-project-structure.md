# Tracy Project Structure

## Objectif

Organiser le projet autour des domaines métier.

Chaque domaine est autonome.

Chaque domaine contient tout ce dont il a besoin.

Le développeur ne cherche jamais dans plusieurs dossiers.

---

# Structure globale

apps/

packages/

shared/

bootstrap/

config/

database/

storage/

tests/

docs/

---

# apps/

Contient les applications exécutables.

Exemple :

apps/

    tracy/

    mantota/

    admin/

Une application assemble les packages.

Elle ne contient presque aucune logique métier.

---

# packages/

Chaque package représente un domaine métier.

packages/

    commerce/

    communication/

    payments/

    crm/

    marketing/

    ai/

    analytics/

    identity/

    observation/

    goals/

    workflow/

---

# Structure d'un package

commerce/

    Domain/

    Application/

    Infrastructure/

    Presentation/

---

# Domain/

Le cœur métier.

Contient :

Entities/

ValueObjects/

Services/

Events/

Policies/

Repositories/

Specifications/

Exceptions/

Capabilities/

Aucune dépendance Laravel.

---

# Application/

Cas d'utilisation.

Contient :

Commands/

Queries/

Handlers/

DTO/

UseCases/

Mappers/

Validators/

Workflows/

Missions/

---

# Infrastructure/

Implémentations techniques.

Contient :

Persistence/

Eloquent/

Redis/

OpenAI/

WhatsApp/

FedaPay/

Storage/

Queue/

Mail/

Logging/

---

# Presentation/

Interface.

Contient :

API/

Controllers/

Requests/

Responses/

Resources/

Console/

Webhooks/

---

# shared/

Composants communs.

shared/

    events/

    auth/

    cache/

    logging/

    notifications/

    contracts/

    testing/

---

# docs/

Toute l'architecture.

Vision/

Architecture/

API/

Business/

ADR/

Roadmap/

---

# tests/

Unit/

Integration/

Feature/

Architecture/

Performance/

---

# Exemple

Commerce

↓

Capabilities

↓

Catalog

↓

UseCase

Créer Produit

↓

Workflow

↓

Service

↓

Repository

↓

Infrastructure

↓

Eloquent

---

# Dépendances

Presentation

↓

Application

↓

Domain

Domain

↓

Aucune dépendance

Infrastructure

↓

Domain

Application

↓

Domain

---

# Règle

Un package ne peut jamais accéder directement au code interne d'un autre package.

Il passe toujours :

- par un contrat
- ou un événement

---

# Objectif final

Construire un projet où chaque domaine est indépendant, compréhensible et facilement testable.