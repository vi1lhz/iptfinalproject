# LootVault – Digital Game Item Marketplace

IPT10 final project: a **custom PHP MVC framework built from scratch** (no Laravel, no Composer packages)
and a **peer-to-peer e-commerce marketplace for digital goods** built on top of it.

- Buy and sell game accounts, in-game items, gift cards, top-ups, digital art, software and music
- All prices in **Philippine pesos (₱)**
- Instant delivery: codes are revealed and files can be downloaded right after payment
- Escrow-style flow: the seller is paid only after the buyer confirms the item works, then the buyer can leave a review
- **External API:** the [FreeToGame](https://www.freetogame.com) game catalog (free, no key or account needed)
- Payment is **simulated** (GCash / Maya / card). It's a school project, so no real money moves.

---

## 1. Requirements

- XAMPP with **PHP 8.1 or newer** (XAMPP 8.1 / 8.2 / 8.3 all work), Apache and MySQL/MariaDB
- PHP extensions `pdo_mysql` and `curl` (both on by default in XAMPP)
- Internet access, for the FreeToGame API. Without it, the app falls back to a bundled offline sample of 10 games.

## 2. Install on XAMPP (Windows)

1. Copy the `lootvault` folder into `C:\xampp\htdocs\` so you have `C:\xampp\htdocs\lootvault\public\index.php`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Create the database using **one** of these:
   - **phpMyAdmin (easiest):** open http://localhost/phpmyadmin, then **Import**, choose `lootvault/database/lootvault.sql`, and click **Go**.
     This creates the `lootvault` database with all tables and demo data.
   - **Command line:** `C:\xampp\php\php.exe cli\migrate.php --fresh --seed` (run it inside the `lootvault` folder).
4. Open **http://localhost/lootvault/**

`.env` is already set for XAMPP's defaults (user `root`, empty password, database `lootvault`).
If your MySQL password differs, edit `DB_PASSWORD` in `.env`.

### Demo accounts (password for all: `password`)

| Role | Email |
|------|-------|
| Admin | admin@lootvault.test |
| Seller (verified) | seller@lootvault.test |
| Buyer | buyer@lootvault.test |

### Zero-setup alternative (no MySQL)

In `.env` set `DB_DRIVER=sqlite` and `DB_DATABASE=storage/database.sqlite`, then run
`php cli/migrate.php --fresh --seed` and `php -S 127.0.0.1:8000 -t public public/index.php`.
(Requires the `pdo_sqlite` extension.)

## 3. Project structure

```
lootvault/
├── core/                 ← THE FRAMEWORK (reusable, knows nothing about the shop)
│   ├── Application.php   boots config, DB, session, router; turns Request → Response; error pages
│   ├── Router.php        URL patterns → controller actions, route groups, middleware pipeline
│   ├── Request.php       wraps $_GET/$_POST/$_SERVER/$_FILES, XAMPP sub-folder detection
│   ├── Response.php      HTML / JSON / redirect / file download
│   ├── Middleware.php    middleware interface
│   ├── Controller.php    base controller: view(), redirect(), json(), validate()
│   ├── View.php          PHP templates + layouts + partials
│   ├── Database.php      PDO wrapper (MySQL + SQLite), prepared statements, transactions
│   ├── Model.php         tiny active-record base (find, where, create, update, delete)
│   ├── Migrator.php      runs database/migrations/*.sql once each
│   ├── Validator.php     rule-based validation (required|email|min:8|unique:users,email …)
│   ├── Session.php       sessions, flash messages, CSRF token
│   ├── Auth.php          login / logout / current user / roles
│   ├── Env.php, Logger.php, helpers.php, bootstrap.php (autoloader)
├── app/                  ← THE APPLICATION (built with the framework)
│   ├── Controllers/      Home, Auth, Item, Game, Cart, Checkout, Order, Seller, Admin
│   ├── Models/           User, Category, Game, Item, CartItem, Order, OrderItem, Review
│   ├── Middleware/       Auth, Guest, Admin, Csrf, Logger, ShareViewData
│   ├── Services/         GameCatalog (FreeToGame API), UploadService
│   └── Views/            templates (layouts, pages, partials)
├── config/               app.php, database.php (read values from .env)
├── routes/web.php        every URL of the app
├── database/             migrations/*.sql, seeds/, lootvault.sql (phpMyAdmin import)
├── public/               index.php (front controller), assets/, uploads/
├── storage/              files/ (protected downloads), logs/, cache/
├── tests/                unit tests (php tests/run.php) + end-to-end script
├── cli/migrate.php       database tool
└── docs/                 documentation PDF, presentation script
```

## 4. Main URLs

| URL | What it does |
|-----|--------------|
| `/` | Home: categories, popular games, top items, how it works |
| `/browse?q=&category=&game=&sort=&min=&max=` | Search and filter listings |
| `/items/{slug}` | Listing page (buy now / add to cart, seller, reviews, game info) |
| `/games`, `/games/{id}` | Game catalog from the FreeToGame API, game details plus its listings |
| `/cart`, `/checkout`, `/orders`, `/orders/{number}` | Buying flow, delivery, confirm and review |
| `/sell`, `/sell/new`, `/sell/{id}/edit` | Seller dashboard and listing form (image + file upload) |
| `/sellers/{username}` | Public seller profile |
| `/admin` … | Admin panel: users (ban/verify), listings, categories, orders, API sync |
| `/api/items`, `/api/games` | The app's own JSON endpoints |

## 5. Tests

```
php tests/run.php                 # 66 unit tests (framework, validation, DB, checkout rules, API client)
bash tests/e2e_flow.sh http://127.0.0.1:8000   # 68 end-to-end checks against the running site (SQLite mode)
```

## 6. What's simulated / not included

- **Payments are simulated.** Choosing GCash, Maya or card creates a paid order with a fake reference
  number. A real gateway (e.g. PayMongo) would plug into `CheckoutController::place()`.
- No email sending (no password reset or receipts).
- No seller withdrawals: the balance is tracked but can't be cashed out.
- No real-time chat between buyer and seller.

## 7. Credits

Game data provided by [FreeToGame.com](https://www.freetogame.com) (attribution shown in the site footer).
Cover images for demo listings were generated for this project.
