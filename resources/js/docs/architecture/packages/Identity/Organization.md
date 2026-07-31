# Organization

## Objectif

Organization représente une entreprise ou une organisation utilisant Tracy.

Une Organization possède un ou plusieurs Workspaces.

---

# Responsabilités

Une Organization peut :

- Créer des Workspaces
- Inviter des utilisateurs
- Définir les paramètres généraux
- Gérer son abonnement
- Gérer sa facturation
- Gérer ses informations légales

Une Organization ne peut pas :

- Gérer directement les campagnes
- Gérer directement les paiements
- Gérer directement les commandes

---

# Attributs

| Nom | Type | Obligatoire | Description |
|------|------|-------------|-------------|
| id | UUID | Oui | Identifiant unique |
| name | String | Oui | Nom de l'organisation |
| slug | String | Oui | Identifiant public unique |
| email | Email | Non | Email principal |
| phone | Phone | Non | Téléphone principal |
| country | Country | Oui | Pays |
| currency | Currency | Oui | Devise |
| timezone | Timezone | Oui | Fuseau horaire |
| logo | String | Non | Logo |
| website | URL | Non | Site web |
| status | OrganizationStatus | Oui | État |
| createdAt | DateTime | Oui | Date de création |
| updatedAt | DateTime | Oui | Dernière modification |

---

# États possibles

- Pending
- Active
- Suspended
- Archived

---

# Relations

Organization

→ Workspace (1..*)

Organization

→ User (N..N via Membership)

---

# Règles métier

- Le nom est obligatoire.
- Le slug est unique.
- Une Organization doit posséder au moins un Workspace.
- Une Organization suspendue ne peut plus créer de nouveaux Workspaces.
- Une Organization archivée est en lecture seule.

---

# Événements

- OrganizationCreated
- OrganizationUpdated
- OrganizationActivated
- OrganizationSuspended
- OrganizationArchived

---

# Cas d'utilisation

- CreateOrganization
- UpdateOrganization
- SuspendOrganization
- ArchiveOrganization
- CreateWorkspace

---

# Hors périmètre

Organization ne gère pas :

- Les utilisateurs
- Les rôles
- Les permissions
- Les paiements
- Les commandes
- Les campagnes
- Les conversations IA