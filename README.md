# Opendatabot IBAN Invoice для WooCommerce

Плагін оплати для WooCommerce: створює рахунок IBAN через Opendatabot і перенаправляє покупця на сторінку оплати. За бажанням — фіксує оплату автоматично через Автоклієнт Opendatabot (callback після надходження коштів на рахунок).

**Підтримка:**

- **WordPress 5.0+**
- **WooCommerce 3.0 – 9.x** (один плагін для всіх популярних версій)
- **PHP 7.0+**
- **Classic Checkout** (shortcode `[woocommerce_checkout]`)
- **Blocks Checkout** (WooCommerce 7.3+ / WordPress 6.0+)
- **HPOS** (High-Performance Order Storage, WC 8.0+)

---

## Встановлення

Є **два шляхи**:

- **A. Через адмін-панель** — завантажити готовий `.zip`, WordPress сам розпакує плагін.
- **B. Вручну** — розархівувати у `wp-content/plugins/` (наприклад через FTP / SSH).

### Що потрібно перед встановленням

1. **Архів плагіна:** `opendatabot-iban.zip` (якщо у вас вихідний код — зберіть скриптом, див. секцію «Для розробників»).
2. **Ключі API Opendatabot** — `x-client-key` і `x-client-name`. Отримати: [iban.opendatabot.ua](https://iban.opendatabot.ua/create-invoice).
3. **IBAN і код** — український IBAN (UA + 27 цифр) і ІПН/ЄДРПОУ (8 або 10 цифр).
4. **Магазин у валюті UAH** — плагін доступний лише для гривні.

### A. Встановлення через адмін-панель

1. Увійдіть у **WordPress admin**.
2. **Плагіни → Додати новий → Завантажити плагін**.
3. Виберіть `opendatabot-iban.zip` → **Встановити зараз** → **Активувати**.
4. **WooCommerce → Налаштування → Платежі** → знайдіть **Opendatabot IBAN Invoice** → **Керувати**.
5. Заповніть поля (див. «Налаштування» нижче) і **Зберегти**.

### B. Встановлення вручну

1. Розархівуйте `opendatabot-iban.zip`. Отримаєте папку `opendatabot-iban/`.
2. Скопіюйте цю папку у `wp-content/plugins/` так, щоб структура була `wp-content/plugins/opendatabot-iban/opendatabot-iban.php`.
3. Увійдіть у адмін → **Плагіни** → знайдіть **Opendatabot IBAN Invoice** → **Активувати**.
4. **WooCommerce → Налаштування → Платежі → Opendatabot IBAN Invoice → Керувати** → заповніть і збережіть.

---

## Налаштування плагіна

**WooCommerce → Налаштування → Платежі → Opendatabot IBAN Invoice → Керувати**

**Основні поля**


| Поле                    | Опис                                                                                                                                                                     |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Увімкнено**           | Вмикає спосіб оплати в чекауті.                                                                                                                                           |
| **Назва**               | Назва способу оплати, яку бачить покупець у списку методів оплати.                                                                                                          |
| **Опис**                | Короткий опис, який показується під назвою в чекауті.                                                                                                                      |
| **IBAN**                | Український IBAN (UA + 27 цифр). Обов'язково.                                                                                                                              |
| **РНОКПП / ЄДРПОУ**     | ІПН або код компанії (8 або 10 цифр). Обов'язково.                                                                                                                        |
| **x-client-key**        | Ключ клієнта API Opendatabot. Обов'язково.                                                                                                                                |
| **x-client-name**       | Ім'я клієнта API (наприклад, `public` або назва застосунку). Обов'язково.                                                                                                  |
| **Призначення платежу** | Текст призначення для рахунку. Плейсхолдер `{order_id}` підставляється номером замовлення. Якщо плейсхолдера немає — номер дописується автоматично.                     |
| **Початковий статус**   | Статус, який ставиться одразу при створенні замовлення (рекомендується **Очікує оплати**).                                                                                |


**Автоклієнт** — опційно, лише якщо у вас налаштований Автоклієнт на iban.opendatabot.ua.


| Поле                    | Опис                                                                                                                                                      |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Автоклієнт**          | Увімкніть, якщо хочете автоматичне підтвердження оплати через бенк-поллінг. При створенні інвойса покупцю буде показана сторінка очікування оплати.        |
| **Статус (оплачено)**   | Статус, який встановиться, коли прийде callback про успішну оплату. Рекомендується **В обробці** / **Виконано**.                                            |
| **Callback URL**        | URL для налаштування у Автоклієнті на iban.opendatabot.ua (поле «Webhook URL»). Формат: `https://ваш-магазин/?wc-api=opendatabot_iban`.                   |


Після змін — **Зберегти зміни**.

---

## Валюта UAH

Спосіб оплати показується **лише коли валюта магазину — UAH**.

Перевірте **WooCommerce → Налаштування → Загальне → Валюта** = `Українська гривня (₴)`.

---

## Усунення несправностей

### Спосіб оплати не видно у чекауті

1. **Валюта не UAH** — див. вище.
2. **Плагін вимкнено** — у налаштуваннях **Увімкнено**.
3. **Порожні обов'язкові поля** — IBAN, РНОКПП/ЄДРПОУ, x-client-key, x-client-name.
4. **Кеш** — очистіть кеш сайту / CDN / об'єктного кешу після налаштування.

### Callback Автоклієнта не фіксує оплату

- Переконайтеся, що `Callback URL` у налаштуваннях Автоклієнта збігається з URL із адмін-панелі плагіна.
- Перевірте логи: **WooCommerce → Статус → Логи** → джерело `opendatabot-iban`.
- Переконайтеся, що ваш сервер доступний публічно (не `localhost`).
- Підпис `Signature` перевіряється HMAC-SHA256 з ключем `x-client-key`.

---

## Обмеження

- Оплата лише в **UAH**.
- Callback Автоклієнта має досягати вашого сервера публічним URL (не `localhost`).
- Потрібен доступ до `https://iban.opendatabot.ua` з вашого сервера (через WP HTTP API — зазвичай cURL).

---

## Сумісність

- **HPOS** (High-Performance Order Storage) — задекларовано як сумісне.
- **Blocks Checkout** — задекларовано як сумісне, метод рендериться через `AbstractPaymentMethodType`.
- **Multisite** — активується per-site.

---

## Для розробників

### Структура репозиторію

```
woocommerce-iban/
├── src/                                          # плагін (кореневі файли для .zip)
│   ├── opendatabot-iban.php                      # main plugin file (headers + bootstrap)
│   ├── uninstall.php
│   ├── readme.txt                                # WP.org-style
│   ├── includes/
│   │   ├── class-opendatabot-iban-gateway.php    # WC_Payment_Gateway
│   │   └── class-opendatabot-iban-blocks.php     # AbstractPaymentMethodType
│   ├── assets/
│   │   ├── js/blocks.js                          # Blocks checkout client
│   │   └── images/icon.png
│   └── languages/
│       ├── opendatabot-iban.pot
│       ├── opendatabot-iban-uk.po
│       └── opendatabot-iban-ru_RU.po
├── scripts/
│   └── build-plugin-zip.sh
└── README.md
```

### Збірка архіву

```bash
./scripts/build-plugin-zip.sh
# → opendatabot-iban.zip у корені репозиторію
```

Скрипт компілює `.po → .mo`, якщо у системі є `msgfmt` (пакет `gettext`). Без нього переклади не потраплять у WP.

### Локальний dev-стенд

Будь-який зручний WordPress + WooCommerce (bedrock, wp-env, LocalWP, Docker). Покладіть вміст `src/` у `wp-content/plugins/opendatabot-iban/` (або зробіть symlink).

### Hooks / Filters

| Hook                               | Опис                                                    |
| ---------------------------------- | ------------------------------------------------------- |
| `opendatabot_iban_icon` (filter)   | Дозволяє змінити URL іконки способу оплати в чекауті.   |

### API endpoint

Callback обробляється через стандартний WooCommerce API endpoint:

```
https://ваш-магазин/?wc-api=opendatabot_iban
```

Payload — JSON POST. Заголовок `Signature` — HMAC-SHA256 тіла запиту з ключем `x-client-key`. Поле `invoiceNumber` у тілі = `order_id` WooCommerce.

---

## Посилання

- [WooCommerce Payment Gateway API](https://woocommerce.com/document/payment-gateway-api/)
- [WooCommerce Blocks — Payment Methods Integration](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md)
- [Opendatabot: створення рахунку IBAN](https://iban.opendatabot.ua/create-invoice)
