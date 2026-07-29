# Architecture des modules de Tracy

## Principe

Tracy est composée de plusieurs modules indépendants qui travaillent ensemble.

Chaque module possède une responsabilité unique.

---

# 1. Tracy Core

Le cœur de Tracy.

Responsabilités :

- Comprendre les demandes.
- Orchestrer les autres modules.
- Gérer les permissions.
- Décider quelles actions exécuter.
- Gérer les conversations.

---

# 2. Tracy AI

Responsabilités :

- Génération des réponses.
- Compréhension du langage naturel.
- Résumé des conversations.
- Classification des intentions.
- Raisonnement.

---

# 3. Tracy Memory

Responsabilités :

- Mémoriser les informations importantes.
- Retenir les préférences des entreprises.
- Conserver l'historique.
- Fournir le contexte à Tracy AI.

---

# 4. Tracy WhatsApp

Responsabilités :

- Recevoir les messages.
- Envoyer les réponses.
- Gérer les médias.
- Gérer les webhooks.
- Gérer les sessions WhatsApp.

---

# 5. Mantota Commerce

Responsabilités :

- Produits.
- Catégories.
- Boutiques.
- Commandes.
- Stocks.
- Clients.
- Paiements.

---

# 6. Tracy Dashboard

Responsabilités :

- Administration.
- Paramètres.
- Statistiques.
- Gestion des utilisateurs.
- Gestion des abonnements.

---

# 7. Tracy Ads

Statut : Désactivé (V1)

Responsabilités futures :

- Création de campagnes.
- Gestion des publicités.
- Analyse des performances.
- Intégration Meta Ads.

---

# 8. Tracy Payments

Responsabilités :

- Wallet.
- Transactions.
- Escrow.
- Paiements Mobile Money.
- Vérification des paiements.

---

# 9. Tracy Notifications

Responsabilités :

- Alertes.
- Notifications.
- Rappels.
- Messages automatiques.

---

# Règle d'or

Aucun module ne doit contenir la logique métier d'un autre module.

Toutes les communications passent par Tracy Core.