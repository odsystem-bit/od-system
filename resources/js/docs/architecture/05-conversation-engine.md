# Moteur de conversation de Tracy

## Principe

Chaque message reçu suit exactement le même pipeline.

Tracy ne répond jamais directement.

Elle analyse, réfléchit, décide puis agit.

---

# Pipeline

Message reçu

↓

Compréhension

↓

Recherche du contexte

↓

Choix de l'action

↓

Exécution

↓

Construction de la réponse

↓

Mémoire

↓

Réponse envoyée

---

# Étape 1 : Réception

Entrées possibles :

- WhatsApp
- Application Tracy
- API
- Dashboard

Toutes les entrées sont transformées dans un format unique.

Exemple :

{
    source: whatsapp,
    sender: 229XXXXXXXX,
    business_id: 15,
    message: "Ajoute un Coca à 500 FCFA"
}

---

# Étape 2 : Compréhension

Tracy identifie :

- la langue
- l'intention
- les entités
- le ton
- l'urgence

Exemple :

Intent :

AJOUT_PRODUIT

Entités :

Nom : Coca

Prix : 500

Devise : FCFA

---

# Étape 3 : Recherche du contexte

Avant de répondre Tracy vérifie :

Qui est cette personne ?

Client ?

Vendeur ?

Administrateur ?

Influenceur ?

---

Elle charge ensuite :

- historique
- mémoire
- paramètres
- boutique
- permissions

---

# Étape 4 : Décision

Tracy choisit une seule action.

Exemple :

Répondre

Créer produit

Modifier produit

Créer commande

Créer boutique

Faire une recherche

Demander une précision

Refuser

---

# Étape 5 : Validation

Certaines actions nécessitent une confirmation.

Exemple :

Supprimer un produit

Supprimer une boutique

Effectuer un remboursement

Annuler une commande

Modifier un paiement

---

# Étape 6 : Exécution

Le module concerné exécute l'action.

Exemple :

Commerce

Paiement

Mémoire

Notifications

---

# Étape 7 : Construction de la réponse

La réponse doit être :

Claire

Courte

Naturelle

Professionnelle

---

# Étape 8 : Sauvegarde

Tracy mémorise uniquement les informations utiles.

Elle ne sauvegarde jamais toute la conversation.

Elle mémorise :

préférences

informations entreprise

contexte

habitudes

---

# Étape 9 : Réponse

La réponse est envoyée.

Le cycle est terminé.