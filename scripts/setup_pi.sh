#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/timepon}"
WEB_ROOT="${WEB_ROOT:-/var/www/timepon}"
PHP_USER="${PHP_USER:-www-data}"
PHP_GROUP="${PHP_GROUP:-www-data}"

echo "[1/5] Install packages"
sudo apt-get update
sudo apt-get install -y php php-cli php-curl php-mbstring lighttpd

echo "[2/5] Prepare directories"
sudo mkdir -p "$APP_DIR" "$WEB_ROOT"

if [[ -d .git ]]; then
  echo "[3/5] Deploy current repository"
  sudo rsync -a --delete --exclude '.git' ./ "$APP_DIR/"
else
  echo "Run this script from repository root" >&2
  exit 1
fi

sudo rm -rf "$WEB_ROOT"
sudo ln -s "$APP_DIR/public" "$WEB_ROOT"

echo "[4/5] Create writable runtime directories"
sudo mkdir -p "$APP_DIR/var/data" "$APP_DIR/var/chimes" "$APP_DIR/var/log"
sudo chown -R "$PHP_USER":"$PHP_GROUP" "$APP_DIR/var"
sudo find "$APP_DIR/var" -type d -exec chmod 775 {} \;
sudo find "$APP_DIR/var" -type f -exec chmod 664 {} \;

if [[ ! -f "$APP_DIR/app/config/config.local.php" ]]; then
  echo "[5/5] Create initial local config"
  sudo tee "$APP_DIR/app/config/config.local.php" >/dev/null <<'PHP'
<?php
return [
  'storage_dir' => __DIR__.'/../../var/data',
  'chime_dir'   => __DIR__.'/../../var/chimes',
  'log_dir'     => __DIR__.'/../../var/log',
];
PHP
  sudo chown "$PHP_USER":"$PHP_GROUP" "$APP_DIR/app/config/config.local.php"
  sudo chmod 664 "$APP_DIR/app/config/config.local.php"
fi

echo "Done. Next:"
echo "  sudo lighttpd-enable-mod fastcgi fastcgi-php"
echo "  sudo systemctl restart lighttpd"
echo "  python3 $APP_DIR/scripts/timepon_cli_tui.py --base-url http://127.0.0.1"
