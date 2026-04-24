#!/bin/sh
set -e

cd /var/www/html

echo "==> Waiting for WordPress core files..."
i=0
while [ ! -f /var/www/html/wp-load.php ]; do
  i=$((i+1))
  if [ "$i" -gt 60 ]; then
    echo "ERROR: wp-load.php not found after 60s"
    exit 1
  fi
  sleep 1
done

# wp-cli uses the shell `mariadb` client for `wp db query`, which stumbles on
# MySQL 8's self-signed TLS cert. Use `wp core is-installed` (PHP/mysqli) as the
# readiness probe instead — and accept both success (installed) and "needs install".
echo "==> Waiting for database via PHP..."
i=0
while true; do
  out=$(wp core is-installed 2>&1 || true)
  case "$out" in
    *"Error establishing a database connection"*|*"MySQL server has gone away"*|*"Can't connect"*)
      ;;
    *)
      break
      ;;
  esac
  i=$((i+1))
  if [ "$i" -gt 60 ]; then
    echo "ERROR: database not reachable after 60s"
    echo "$out"
    exit 1
  fi
  sleep 1
done

if ! wp core is-installed 2>/dev/null; then
  echo "==> Installing WordPress..."
  wp core install \
    --url="${WP_URL}" \
    --title="IBAN Dev Shop" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
else
  echo "==> WordPress already installed."
fi

echo "==> Ensuring Ukrainian language..."
wp language core install uk --activate || true

if ! wp plugin is-installed woocommerce; then
  echo "==> Installing WooCommerce..."
  wp plugin install woocommerce --activate
else
  echo "==> Ensuring WooCommerce is active..."
  wp plugin activate woocommerce || true
fi

echo "==> Setting store country to Ukraine and currency to UAH..."
wp option update woocommerce_default_country "UA:30"
wp option update woocommerce_currency "UAH"
wp option update woocommerce_currency_pos "right_space"
wp option update woocommerce_price_decimal_sep ","
wp option update woocommerce_price_thousand_sep " "
wp option update woocommerce_store_address "Main St. 1"
wp option update woocommerce_store_city "Kyiv"
wp option update woocommerce_store_postcode "01001"
wp option update blogname "IBAN Dev Shop"

echo "==> Activating Opendatabot IBAN plugin..."
wp plugin activate opendatabot-iban || true

if ! wp post list --post_type=product --format=ids | grep -q .; then
  echo "==> Creating a sample product..."
  wp post create --post_type=product --post_status=publish --post_title="Тестовий товар" --porcelain | \
    xargs -I{} sh -c 'wp post meta update {} _price 100 && wp post meta update {} _regular_price 100 && wp post meta update {} _visibility visible && wp post meta update {} _stock_status instock && wp wc product_cat create --name="Test" --user=1 2>/dev/null || true'
fi

echo ""
echo "========================================================"
echo "  WordPress dev stack is ready."
echo "  URL:      ${WP_URL}"
echo "  Admin:    ${WP_URL}/wp-admin/"
echo "  Login:    ${WP_ADMIN_USER} / ${WP_ADMIN_PASSWORD}"
echo "  Plugin:   WooCommerce → Налаштування → Платежі → Opendatabot IBAN Invoice"
echo "========================================================"
