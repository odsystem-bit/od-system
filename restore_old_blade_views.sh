#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OLD_ROOT="${1:-}"
NEW_ROOT="$ROOT_DIR/resources/views"
BACKUP_DIR="${2:-$ROOT_DIR/restore_blade_backup_$(date +%Y%m%d%H%M%S)}"

FILES=(
  admin.blade.php
  vendor.blade.php
  influencer.blade.php
  app.blade.php
)

if [[ -z "$OLD_ROOT" ]]; then
  echo "Usage: $0 /chemin/vers/ancienne/version/resources/views [backup_destination]"
  exit 1
fi

if [[ ! -d "$OLD_ROOT" ]]; then
  echo "Erreur : dossier source introuvable : $OLD_ROOT"
  exit 1
fi

mkdir -p "$BACKUP_DIR"

echo "Backup des fichiers actuels dans : $BACKUP_DIR"
for file in "${FILES[@]}"; do
  src="$NEW_ROOT/$file"
  dest="$BACKUP_DIR/$file"
  if [[ ! -f "$src" ]]; then
    echo "Attention : fichier actuel introuvable, impossible de sauvegarder : $src"
    continue
  fi
  cp -p "$src" "$dest"
  echo "Sauvegardé : $src -> $dest"
done


echo "Restauration des anciens fichiers Blade depuis : $OLD_ROOT"
for file in "${FILES[@]}"; do
  oldfile="$OLD_ROOT/$file"
  newfile="$NEW_ROOT/$file"

  if [[ ! -f "$oldfile" ]]; then
    echo "Erreur : fichier source ancien introuvable : $oldfile"
    exit 1
  fi

  cp -p "$oldfile" "$newfile"
  echo "Restauré : $oldfile -> $newfile"
done


echo "Restauration terminée. Vérifie le contenu dans $NEW_ROOT."
