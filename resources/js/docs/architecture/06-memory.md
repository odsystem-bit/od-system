# Architecture de la mémoire de Tracy

## Objectif

La mémoire permet à Tracy d'éviter de demander les mêmes informations plusieurs fois.

Elle lui permet également de personnaliser ses réponses et d'agir intelligemment.

Une mémoire ne consiste pas à enregistrer toutes les conversations.

Une mémoire consiste à conserver uniquement les informations utiles.

---

# Les 5 types de mémoire

## 1. Mémoire de l'entreprise

Contient les informations permanentes.

Exemples :

- Nom de l'entreprise
- Adresse
- Horaires
- Numéro WhatsApp
- Email
- Devise
- Langue
- Livraison
- Politique de retour
- Moyens de paiement
- Logo
- Réseaux sociaux

Cette mémoire change rarement.

---

## 2. Mémoire métier

Contient les informations nécessaires au fonctionnement.

Exemples :

- Produits
- Catégories
- Stock
- Prix
- Promotions
- Commandes
- Clients
- Fournisseurs

Elle provient principalement de Mantota.

---

## 3. Mémoire client

Une mémoire différente pour chaque client.

Exemples :

- Nom
- Téléphone
- Langue préférée
- Dernier achat
- Produits favoris
- Ville
- Adresse
- Historique des commandes

---

## 4. Mémoire conversationnelle

Très courte durée.

Elle sert uniquement à comprendre la conversation actuelle.

Exemple :

Utilisateur :

"Je veux celui de 128 Go."

Tracy sait que "celui" fait référence au téléphone évoqué quelques messages plus tôt.

Cette mémoire disparaît à la fin de la conversation.

---

## 5. Mémoire d'apprentissage

Tracy apprend progressivement.

Exemple :

Le vendeur écrit toujours :

"Portable"

au lieu de

"Téléphone"

Tracy comprend que les deux désignent le même produit dans ce contexte.

---

# Règles de mémorisation

Tracy ne mémorise jamais automatiquement.

Avant d'ajouter une information, elle vérifie :

- Est-elle utile ?
- Est-elle durable ?
- Est-elle fiable ?
- Est-elle confirmée ?

Si la réponse est NON, elle ne l'enregistre pas.

---

# Mise à jour

Une information existante peut être :

- remplacée
- supprimée
- complétée

Toutes les modifications sont historisées.

---

# Recherche

Avant chaque réponse, Tracy consulte :

1. Mémoire conversationnelle
2. Mémoire client
3. Mémoire entreprise
4. Mémoire métier

Dans cet ordre.

---

# Oubli

Certaines informations expirent automatiquement.

Exemples :

Conversation en cours

Panier temporaire

OTP

Code de validation

Session

---

# Sécurité

Chaque entreprise possède sa propre mémoire.

Une entreprise ne peut jamais accéder à la mémoire d'une autre.

Toutes les mémoires sont isolées.