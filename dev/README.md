# Dev-стенд (Docker)

Самодостатній локальний стенд: MySQL 8 + WordPress (php 8.2 + apache) + WooCommerce + активований плагін.

## Запуск

```bash
# (опційно) кастомізувати порт та креденшіали
cp dev/.env.example dev/.env

# підняти стек
docker compose --env-file dev/.env -f dev/docker-compose.yml up -d
```

Перший запуск: сервіс `wpcli` сам поставить WordPress, встановить WooCommerce, переключить магазин на валюту **UAH**, активує плагін і створить тестовий товар. Його логи:

```bash
docker compose -f dev/docker-compose.yml logs -f wpcli
```

Коли побачите `WordPress dev stack is ready` — відкривайте:

- **Магазин:** http://localhost:8082/
- **Адмін:** http://localhost:8082/wp-admin/ (логін `admin` / `admin`)
- **Налаштування плагіна:** WooCommerce → Налаштування → Платежі → Opendatabot IBAN Invoice → **Керувати**

## Життєвий цикл

```bash
# зупинити (зберегти БД і файли)
docker compose -f dev/docker-compose.yml down

# зупинити і стерти все (наступний up буде fresh install)
docker compose -f dev/docker-compose.yml down -v

# перевстановити тільки wpcli (повторити bootstrap)
docker compose -f dev/docker-compose.yml up -d --force-recreate wpcli
```

## Живе редагування коду

`src/` прокинуто bind-mount-ом у `/var/www/html/wp-content/plugins/opendatabot-iban`. Правки у файлах плагіна видно одразу, без перезапуску контейнера.

> **Note:** рядки перекладу `.po` на льоту не підтягуються — WordPress вантажить `.mo`. Щоб подивитись нові переклади, перезберіть `.mo` через `msgfmt`:
>
> ```bash
> docker compose -f dev/docker-compose.yml run --rm --entrypoint sh wpcli -c \
>   'for po in /var/www/html/wp-content/plugins/opendatabot-iban/languages/*.po; do \
>     mo="${po%.po}.mo"; echo "$po -> $mo"; msgfmt -o "$mo" "$po"; done' 2>/dev/null || \
> (command -v msgfmt >/dev/null && for po in src/languages/*.po; do msgfmt -o "${po%.po}.mo" "$po"; done)
> ```

## Перевстановлення з нуля

```bash
docker compose -f dev/docker-compose.yml down -v && \
  docker compose -f dev/docker-compose.yml up -d
```

## Callback (Автоклієнт)

Callback URL локально: `http://localhost:8082/?wc-api=opendatabot_iban`.
Щоб Opendatabot міг його досягти — прокиньте через `ngrok http 8082` або аналог.

## Тестування встановлення через адмінку (як .zip)

За замовчуванням код плагіна bind-mount-иться. Щоб перевірити встановлення через WP-admin → Plugins → Upload:

```bash
# закоментувати в docker-compose.yml volume з ../src у wordpress та wpcli
# перезапустити
docker compose -f dev/docker-compose.yml up -d --force-recreate wordpress wpcli

# збудувати .zip
./scripts/build-plugin-zip.sh

# завантажити opendatabot-iban.zip через адмінку:
#   http://localhost:8082/wp-admin/plugin-install.php?tab=upload
```
