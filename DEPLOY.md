# Deploying Lapak (Hostinger Single — shared, no SSH)

The production target is **Hostinger's "Single" shared plan**, backed by
**MariaDB**. This tier has **no SSH** (confirmed with Hostinger support — SSH
starts at Web Premium), and hPanel's Git integration only *copies files* — it
runs no `composer`, `npm`, or `artisan`. So the app is **built in GitHub Actions**
and a pre-built `deploy` branch is **auto-deployed by hPanel Git**.

```
 push to main ──> GitHub Actions ──> force-push built tree ──> deploy branch
                  (composer + npm)                                  │
                                                       hPanel Git auto-deploy
                                                                    │
                                                          Hostinger (serves it)
```

> **Compatibility is verified.** Schema, the full seed (~1,400 sales over 60
> days), and the entire test suite (26 tests) run green against **MariaDB 10.4**
> under Laravel's strict SQL mode. The `mysql` driver is used (broadly compatible
> with MariaDB and what hPanel's tutorials assume). Local dev stays on SQLite.

---

## 1. Requirements on the host

| Need            | Notes                                                        |
| --------------- | ----------------------------------------------------------- |
| PHP **8.2+**    | hPanel → Advanced → PHP Configuration. Extensions: **`pdo_mysql`**, **`gd`**, `mbstring`, `openssl`, `bcmath`, `ctype`, `fileinfo`, `xml` |
| **MariaDB**     | one database + one user (created in hPanel → Databases)      |
| GitHub account  | the repo, connected to hPanel Git over OAuth                 |

`gd` powers the product-image → WebP pipeline; `pdo_mysql` the database. Composer
and Node are **not** needed on the host — the build happens in CI.

---

## 2. One-time GitHub Actions setup

The workflow lives at `.github/workflows/deploy-hostinger.yml`. It needs no
secrets — it pushes the `deploy` branch using the built-in `GITHUB_TOKEN`
(`permissions: contents: write` is set in the file).

1. Push `main` to GitHub (or trigger **Actions → Build and Deploy to Hostinger →
   Run workflow**). The job installs prod dependencies, builds assets, and
   force-pushes the result to a **`deploy`** branch.
2. Confirm the `deploy` branch appears in the repo and contains `vendor/` and
   `public/build/` (force-added past `.gitignore` on purpose).

If pushing the branch is rejected, enable **Settings → Actions → General →
Workflow permissions → Read and write permissions** on the repo.

---

## 3. One-time hPanel Git setup

In hPanel → **Websites → Advanced → GIT**:

1. **Create repository** → authorize GitHub (OAuth), pick this repo, set
   **branch = `deploy`**, and choose the install directory (see §6 about the
   document root).
2. Enable **auto-deployment** so each new commit on `deploy` is pulled
   automatically. (hPanel shows a webhook URL; with auto-deploy on, pushes from
   CI deploy without manual action.)

---

## 4. Create the database

hPanel → **Databases → MySQL Databases**: create a database and a user (Hostinger
prefixes both, e.g. `u123456789_lapak`), and grant the user all privileges on it.
Note the database name, username, and password for the next step.

---

## 5. Configure `.env` (once, via File Manager)

There is no SSH, so create `.env` by hand:

1. hPanel → **Files → File Manager**, open the deployed app directory.
2. Copy `.env.production.example` to `.env`.
3. Fill in:
   - `APP_URL` — your public URL
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — from §4
   - `SESSION_DOMAIN` — your domain (keep `SESSION_SECURE_COOKIE=true` on HTTPS)
   - `APP_KEY` — generate locally (`php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`) and paste it, since there's no SSH to run `key:generate`.
   - `DEPLOY_MIGRATE_TOKEN` — only if you'll use the migration route (§7, fallback)

`APP_ENV=production` and `APP_DEBUG=false` are already set in the template.

> ⚠️ **`.env` and redeploys.** `.env` is **not** on any branch. hPanel Git
> auto-deploy can overwrite/remove untracked files when it syncs the `deploy`
> branch. **Back up `.env` (download a copy) before the first real redeploy** and
> confirm it survives. If it gets wiped, restore it from the backup. Watch the
> same for `public/uploads/` (uploaded product images live there).

---

## 6. Document root

Laravel must be served from its **`public/`** directory — never the project root
(that would expose `.env`). Hostinger's default web root is `public_html`. Two
workable layouts:

- **Deploy beside `public_html`** (recommended): set the hPanel Git install
  directory to a folder *next to* `public_html` (e.g. `~/lapak`), then make
  `public_html` a **symlink** to `~/lapak/public`, or put a tiny `index.php` in
  `public_html` that requires `../lapak/public/index.php` and the autoloader.
- **Set the doc root to the app's `public/`** if your plan lets you change the
  website's document root in hPanel.

Pick whichever the plan allows and **verify on the first deploy** that
`https://your-domain` serves the app and that `https://your-domain/.env` is
**not** reachable.

---

## 7. Run migrations (no SSH)

Migrations are applied by the idempotent command
**`php artisan app:run-pending-migrations`** (a safe wrapper over
`migrate --force`). Trigger it one of two ways:

### Preferred — hPanel Cron Job (no public surface)

hPanel → **Advanced → Cron Jobs** → add a job running:

```
/usr/bin/php /home/uXXXXXXXXX/<app-path>/artisan app:run-pending-migrations >> /home/uXXXXXXXXX/migrate.log 2>&1
```

Schedule it to run periodically (e.g. every few minutes/hours — confirm the
minimum interval allowed on Single). It's a no-op when nothing is pending, so
running it often is harmless. Cron can run **any** artisan command, so a one-off
job with `php artisan db:seed --force` is also how you'd load demo data (see §8).

### Fallback — token-protected route (when cron isn't available)

Set `DEPLOY_MIGRATE_TOKEN` in `.env` to a long random value
(`php -r "echo bin2hex(random_bytes(24)).PHP_EOL;"`), then after each deploy hit:

```
https://your-domain/deploy/migrate?token=YOUR_TOKEN
```

(browser, or `curl`/`wget`). It runs pending migrations and prints the result.

Why this is safe to leave in the repo permanently — necessary, since files on the
`deploy` branch reappear on every redeploy:

- **Disabled by default:** 404s unless `DEPLOY_MIGRATE_TOKEN` is set.
- **Constant-time token check;** a wrong/missing token 404s (no existence leak).
- **Only ever runs idempotent migrations** — no data exposure, nothing
  destructive.
- **Works on the first deploy:** the route is registered without session/CSRF
  middleware, so it doesn't need the (not-yet-created) `sessions` table.

> If you use cron, leave `DEPLOY_MIGRATE_TOKEN` blank so the route stays disabled.

---

## 8. Seed roles (and optional demo data)

The app needs **roles & permissions** seeded, or every role-guarded route returns
403. Roles live in `DatabaseSeeder::seedRoles()`. Without SSH, run the seeder via
a **one-off hPanel Cron Job**:

- **Portfolio demo (accounts + ~60 days of sales):** `php artisan db:seed --force`
  — seeded logins use the password `password` (see the README). Dates are
  relative to the seed run, so re-seed if the data looks stale.
- **Real production (no demo data):** extract `seedRoles()` into its own
  `RoleSeeder` and run only that (`php artisan db:seed --class=RoleSeeder
  --force`), then create your real owner account. *(The current seeder always
  bundles demo data — intentional for the portfolio demo.)*

Remove the one-off cron job afterwards.

---

## 9. File permissions

`storage/` and `bootstrap/cache/` must be writable by the web user, and product
images are written under `public/uploads/products` (with an `.htaccess` that
disables script execution there). On shared hosting these are usually correct by
default; if you see write errors, set them to `755`/`775` via File Manager →
Permissions. No `storage:link` is required (uploads go straight under `public/`).

> Config caching (`config:cache`/`route:cache`) is **skipped** — it needs CLI
> access this plan doesn't reliably offer, and the app runs correctly without it
> at this traffic level. If you later want it, run it from a cron job once.

---

## Post-deploy checklist

- [ ] `https://your-domain` loads the login page over HTTPS; `/.env` is **not** reachable
- [ ] Migrations have run (§7); signing in works (demo owner, or your account)
- [ ] Owner sees **Reports** with populated charts; **Export PDF** returns a PDF
- [ ] A test sale completes and stock decrements
- [ ] Uploading a product photo succeeds and renders (confirms `gd` + perms)
- [ ] The passwordless `/login/as/{role}` demo buttons are **absent** (local/testing only, by design)
- [ ] `.env` and `public/uploads/` survived the deploy (back them up first)

---

## Troubleshooting

| Symptom | Likely cause / fix |
| ------- | ------------------ |
| **`deploy` branch missing dependencies / unstyled site** | CI didn't force-add `vendor`/`public/build`, or the build failed — check the Actions run log. |
| **Actions can't push `deploy`** | Repo → Settings → Actions → Workflow permissions → **Read and write**. |
| **500 on every page** | Missing/incorrect `APP_KEY`, unwritable `storage/`, or DB credentials wrong. Temporarily set `APP_DEBUG=true` (then revert) and check `storage/logs/laravel.log`. |
| **Everyone gets 403** | Roles weren't seeded — see §8. |
| **`/deploy/migrate` returns 404** | `DEPLOY_MIGRATE_TOKEN` not set in `.env`, or token mismatch (by design). |
| **`could not find driver`** | `pdo_mysql` not enabled for the active PHP version (hPanel → PHP Configuration). |
| **Image upload fails / 500** | `gd` extension missing, or `public/uploads/products` not writable. |
| **`.env` disappeared after a deploy** | Auto-deploy wiped untracked files — restore from backup; keep `.env` outside the deploy dir if it recurs. |
