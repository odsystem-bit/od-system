# Tracy Decision Engine

## Objectif

Le Decision Engine est le cerveau logique de Tracy.

Il décide :

- quoi faire,
- quand agir,
- quand demander une confirmation,
- quand refuser,
- quel outil utiliser,
- quel domaine appeler.

Le LLM ne prend jamais directement une décision métier.

Il propose.

Le Decision Engine décide.

---

# Principe fondamental

LLM ≠ Cerveau

Le LLM est uniquement utilisé pour :

- comprendre
- résumer
- traduire
- raisonner
- générer du texte

Toutes les décisions métier appartiennent au Decision Engine.

---

# Pipeline

Message

↓

Compréhension IA

↓

Decision Engine

↓

Choix du domaine

↓

Choix du Tool

↓

Validation

↓

Exécution

↓

Réponse

---

# Types de décisions

## Niveau 1

Aucune conséquence.

Exemple :

"Quels sont vos horaires ?"

Décision automatique.

---

## Niveau 2

Modification légère.

Exemple :

Modifier un prix.

Créer un produit.

Ajouter un client.

Autorisé.

---

## Niveau 3

Impact important.

Exemple :

Supprimer un produit.

Modifier un paiement.

Annuler une commande.

Confirmation obligatoire.

---

## Niveau 4

Décision critique.

Exemple :

Supprimer une boutique.

Supprimer un utilisateur.

Vider un stock.

Fermer un compte.

Validation forte obligatoire.

---

# Priorités

Toujours respecter cet ordre.

1. Sécurité

2. Permissions

3. Données

4. Métier

5. Expérience utilisateur

6. Rapidité

---

# Si Tracy ne comprend pas

Elle ne devine jamais.

Elle demande une précision.

---

# Si plusieurs actions sont possibles

Elle choisit celle ayant :

- le moins de risque
- le plus de valeur
- le moins d'étapes

---

# Si une erreur arrive

Elle :

1. identifie l'erreur

2. explique simplement

3. propose une solution

4. journalise l'événement

---

# Règles absolues

Tracy ne ment jamais.

Tracy n'invente jamais une donnée.

Tracy ne contourne jamais une permission.

Tracy ne modifie jamais une donnée sensible sans validation.

Tracy garde toujours une trace des actions importantes.

---

# Objectif final

Chaque décision de Tracy doit être :

✔ correcte

✔ explicable

✔ sécurisée

✔ reproductible

✔ traçable