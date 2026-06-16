#!/usr/bin/env bash
set -euo pipefail

DB_NAME="mir_igrushek_bd"
DB_PASS="skzskz"
WEBROOT="/var/www/html"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ $EUID -ne 0 ]]; then
  echo "Запустите через sudo:  sudo bash setup.sh" >&2
  exit 1
fi

echo "==> [1/6] Установка пакетов (Apache, MySQL, PHP)…"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring

echo "==> [2/6] Запуск служб…"
systemctl enable --now apache2
systemctl enable --now mysql

echo "==> [3/6] Разрешаем подключение к MySQL по сети (bind-address = 0.0.0.0)…"
CNF="/etc/mysql/mysql.conf.d/mysqld.cnf"
if [[ -f "$CNF" ]]; then
  sed -i 's/^[[:space:]]*bind-address.*/bind-address = 0.0.0.0/' "$CNF"
  grep -q '^bind-address' "$CNF" || echo 'bind-address = 0.0.0.0' >> "$CNF"
  sed -i 's/^[[:space:]]*mysqlx-bind-address.*/mysqlx-bind-address = 0.0.0.0/' "$CNF" || true
fi
systemctl restart mysql

echo "==> [4/6] Создание базы данных и загрузка данных…"
if mysql -u root -e "SELECT 1" >/dev/null 2>&1; then
  mysql -u root --default-character-set=utf8mb4 < "$HERE/init.sql"
else
  mysql -u root -p"$DB_PASS" --default-character-set=utf8mb4 < "$HERE/init.sql"
fi

echo "==> [5/6] Развёртывание сайта в $WEBROOT…"
rm -f "$WEBROOT/index.html"
mkdir -p "$WEBROOT/images"
cp -rf "$HERE/web/." "$WEBROOT/"
[[ -d "$HERE/images" ]] && cp -rf "$HERE/images/." "$WEBROOT/images/"
[[ -f "$HERE/report.docx" ]] && cp -f "$HERE/report.docx" "$WEBROOT/report.docx"
chown -R www-data:www-data "$WEBROOT"
a2enmod php* >/dev/null 2>&1 || true
if ! grep -q 'DirectoryIndex index.php' /etc/apache2/mods-enabled/dir.conf 2>/dev/null; then
  sed -i 's/DirectoryIndex .*/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf || true
fi
systemctl reload apache2

echo "==> [6/6] Открываем порты 80 и 3306 в фаерволе…"
if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then
  ufw allow 80/tcp   || true
  ufw allow 3306/tcp || true
fi

IP="$(hostname -I | awk '{print $1}')"
echo
echo "============================================================"
echo "  ГОТОВО."
echo "  Сайт:               http://$IP/"
echo "  MySQL Workbench:    host=$IP  port=3306  user=root  pass=$DB_PASS"
echo "  База данных:        $DB_NAME"
echo "  Пользователи сайта: логин = email из задания, пароль = $DB_PASS"
echo "============================================================"
