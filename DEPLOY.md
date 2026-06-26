# Deploying Lapak (MariaDB shared host)

This guide deploys Lapak to a typical **cPanel-style shared host** backed by
**MariaDB** — the production target for the project. The same steps apply to a
VPS; only the "set the document root" part differs.

> **Compatibility is verified.** The schema, the full seed (~1,400 sales over
> 60 days), and the entire PHPUnit suite (22 tests) all run green against
> **MariaDB 10.4** with Laravel's strict SQL mode (`ONLY_FULL_GROUP_BY`
> included). Local development still uses SQLite; only the deploy target is
> MariaDB.

---

## 1. Requirements on the host

| Need            | Notes                                                        |
| --------------- | ----------------------------------------------------------- |
| PHP **8.2+**    | with extensions **`pdo_mysql`**, **`gd`**, `mbstring`, `openssl`, `bcmath`, `ctype`, `fileinfo`, `xml` |
| **MariaDB 10.4+** (or MySQL 8) | one database + one user with full privileges on it |
| Composer        | to install PHP dependencies                                  |
| Node.js 18+     | to build front-end assets — **locally if the host has no Node** (see step 5) |

Quick extension check (run on the host, or via a one-line PHP script):

```bash
php -m | grep -Ei 'pdo_mysql|gd|mbstring|fileinfo'
```

`gd` is required for the product-image → WebP pipeline; `pdo_mysql` for the
database. If either is missing, enable it in the host's PHP settings (cPanel:
*Select PHP Version → Extensions*).

---

## 2. Create the database

In cPanel **MySQL® Databases** (or via SQL):

```sql
CREATE DATABASE cpuser_lapak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cpuser_lapak'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON cpuser_lapak.* TO 'cpuser_lapak'@'localhost';
FLUSH PRIVILEGES;
```

Note the **prefixed** names cPanel assigns (e.g. `cpuser_lapak`) — you'll plug
them into `.env` next.

---

## 3. Get the code onto the server

```bash
git clone https://github.com/filan214/POS.git lapak
cd lapak
composer install --no-dev --optimize-autoloader
```

(No git on the host? Build a release locally — `composer install --no-dev` —
and upload everything **except** `node_modules/` and `.git/`.)

---

## 4. Configure the environment

```bash
cp .env.production.example .env
php artisan key:generate
```

Then edit `.env` and set at least:

- `APP_URL` — your public URL
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — from step 2
- `SESSION_DOMAIN` — your domain (and keep `SESSION_SECURE_COOKIE=true` if HTTPS)

`APP_ENV=production` and `APP_DEBUG=false` are already set in the template.

---

## 5. Build front-end assets

Vite compiles Tailwind/Alpine into `public/build`. If the host has Node:

```bash
npm ci
npm run build
```

If it doesn't (common on shared hosting), run the build **locally** and upload
the generated `public/build/` directory. Assets are content-hashed, so just
keep `public/build` in sync with the deployed code.

---

## 6. Migrate and seed

```bash
php artisan migrate --force
```

Then seed. **Choose one:**

- **Live portfolio demo** — load the demo accounts and ~60 days of sales so the
  dashboard looks alive:

  ```bash
  php artisan db:seed --force
  ```

  Seeded logins use the password `password` (see the README). Dates are
  relative to the seed run, so re-seed if the data ever looks stale.

- **Real production (no demo data)** — you still need the **roles &
  permissions**, or every role-guarded route returns 403. The role setup lives
  in `DatabaseSeeder::seedRoles()`; for a clean production install, extract it
  into its own seeder (e.g. `RoleSeeder`) and run only that, then create your
  real owner account manually. *(The current seeder always includes demo data,
  which is intentional for the portfolio demo.)*

---

## 7. Cache config for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Re-run these after any `.env` change. To undo: `php artisan optimize:clear`.

---

## 8. Point the web server at `public/`

The document root **must** be the `public/` directory — never the project root
(that would expose `.env`).

- **VPS / full control:** set the vhost `DocumentRoot` to `.../lapak/public`.
- **Shared host where the doc root is fixed** (e.g. `public_html`): put the app
  one level above `public_html`, then either symlink
  (`ln -s ../lapak/public/* public_html/`) or move the contents of `public/`
  into `public_html` and fix the two `require` paths in `public_html/index.php`
  to point at the app directory. Symlinking is cleaner.

---

## 9. File permissions

These must be writable by the web server user:

```bash
chmod -R ug+rw storage bootstrap/cache
mkdir -p public/uploads/products && chmod -R ug+rw public/uploads
```

Product images are written directly under `public/uploads/products` (with an
`.htaccess` that disables script execution there) — no `storage:link` is
required for them. Run `php artisan storage:link` only if you later use the
`storage` disk for public files.

---

## Post-deploy checklist

- [ ] `https://your-domain` loads the login page over HTTPS
- [ ] You can sign in (demo owner, or your real account)
- [ ] Owner sees **Reports** with populated charts; **Export PDF** returns a PDF
- [ ] A test sale completes and stock decrements
- [ ] Uploading a product photo succeeds and renders (confirms `gd` + perms)
- [ ] The passwordless `/login/as/{role}` demo buttons are **absent** — they are
      registered only in `local`/`testing`, never in production (by design)

---

## Troubleshooting

| Symptom | Likely cause / fix |
| ------- | ------------------ |
| **500 on every page** | Missing `APP_KEY` (`php artisan key:generate`), unwritable `storage/`, or a stale cache — run `php artisan optimize:clear`. Check `storage/logs/laravel.log`. |
| **Everyone gets 403** | Roles weren't seeded — see step 6. Then `php artisan permission:cache-reset` if available, or `php artisan optimize:clear`. |
| **`could not find driver`** | `pdo_mysql` not enabled for the active PHP version. |
| **Image upload fails / 500** | `gd` extension missing, or `public/uploads/products` not writable. |
| **CSS/JS missing (unstyled page)** | `public/build` not deployed — re-run step 5. |
| **Config changes ignored** | Cached config — re-run step 7 (or `optimize:clear`). |
