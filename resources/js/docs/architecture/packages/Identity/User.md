# User

## Objectif

User représente une personne possédant un compte sur Tracy.

Un User peut appartenir à une ou plusieurs Organizations via des Workspaces.

Le User n'est jamais lié directement aux paiements, commandes ou campagnes.

---

# Responsabilités

Le User peut :

- Se connecter
- Se déconnecter
- Modifier son profil
- Modifier son mot de passe
- Vérifier son email
- Vérifier son numéro de téléphone
- Rejoindre un Workspace
- Quitter un Workspace
- Recevoir des invitations

Le User ne peut pas :

- Gérer les permissions directement
- Modifier son propre rôle
- Accéder aux données d'un autre utilisateur sans autorisation

---

# Attributs

| Nom | Type | Obligatoire | Description |
|------|------|-------------|-------------|
| id | UUID | Oui | Identifiant unique |
| firstName | String | Oui | Prénom |
| lastName | String | Oui | Nom |
| email | Email | Oui | Adresse email |
| phone | Phone | Non | Numéro de téléphone |
| password | PasswordHash | Oui | Mot de passe chiffré |
| avatar | String | Non | Photo de profil |
| status | UserStatus | Oui | État du compte |
| emailVerifiedAt | DateTime | Non | Date de validation de l'email |
| phoneVerifiedAt | DateTime | Non | Date de validation du téléphone |
| createdAt | DateTime | Oui | Date de création |
| updatedAt | DateTime | Oui | Dernière modification |

---

# États possibles

- Pending
- Active
- Suspended
- Deleted

---

# Relations

User

→ Membership (1..*)

Membership

→ Workspace

Workspace

→ Organization

---

# Règles métier

- L'email doit être unique.
- Le numéro de téléphone peut être unique.
- Le mot de passe n'est jamais stocké en clair.
- Un utilisateur supprimé ne peut plus se connecter.
- Un utilisateur suspendu ne peut effectuer aucune action.
- Un utilisateur peut appartenir à plusieurs Workspaces.
- Les rôles sont attribués via Membership.

---

# Événements

- UserRegistered
- UserActivated
- UserSuspended
- UserDeleted
- EmailVerified
- PhoneVerified
- PasswordChanged

---

# Cas d'utilisation

- RegisterUser
- AuthenticateUser
- UpdateProfile
- ChangePassword
- VerifyEmail
- VerifyPhone
- JoinWorkspace
- LeaveWorkspace

---

# Hors périmètre

Le User ne gère pas :

- Wallet
- Paiements
- Produits
- Commandes
- Campagnes
- Tickets
- Conversations IA