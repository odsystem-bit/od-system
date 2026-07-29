# Les outils de Tracy

## Principe

Tracy ne fait jamais une action directement.

Elle utilise toujours un outil (Tool).

Le moteur IA décide QUOI faire.

Les Tools savent COMMENT le faire.

Ainsi, Tracy peut évoluer sans modifier son intelligence.

---

# Catégories de Tools

## Commerce

- create_store
- update_store
- delete_store

- create_product
- update_product
- delete_product

- search_product

- create_order
- update_order
- cancel_order

---

## Paiement

- create_payment

- verify_payment

- refund_payment

- wallet_balance

- transaction_history

---

## Utilisateurs

- create_customer

- update_customer

- search_customer

- block_customer

---

## Notifications

- send_whatsapp

- send_email

- send_sms

- push_notification

---

## IA

- summarize

- translate

- classify

- generate_text

- generate_image

---

## Mémoire

- save_memory

- search_memory

- update_memory

- delete_memory

---

## Internet

- search_web

- crawl_website

- import_products

---

## Statistiques

- sales_report

- stock_report

- dashboard

---

## Publicité

(V2)

- create_campaign

- publish_campaign

- analyze_campaign

---

# Règles

Chaque Tool possède :

- un nom unique
- une description
- des permissions
- des paramètres d'entrée
- un résultat standardisé
- une gestion des erreurs

Aucun Tool ne peut accéder directement à la base de données sans passer par les services du domaine.