# PRD: Point of Sale (POS) System for Minimarket/Warung

**Version:** 1.0
**Date:** June 20, 2026
**Owner:** Filan
**Status:** Draft — ready for implementation planning

---

## 1. Overview

A point-of-sale web application for a single-cashier, single-location minimarket (warung). The system replaces manual cash-register bookkeeping with a structured workflow covering sales transactions, stock management, shift cash reconciliation, and sales/profit reporting — while doubling as a portfolio piece demonstrating full-stack depth.

The application is built with **Laravel**, deployed to a **shared hosting** environment (cPanel-based, no SSH access), and supports a **bilingual interface (English / Indonesian)**.

---

## 2. Goals

- Digitize the full cashier workflow: transaction → stock deduction → shift reconciliation → reporting.
- Keep hosting cost minimal (~Rp 17,900/month) while remaining fully Terms-of-Service compliant for commercial/business use (unlike free-tier platforms such as Vercel Hobby, which prohibit commercial usage).
- Demonstrate technical depth appropriate for a portfolio: role-based access control, atomic stock transactions, hardware integration (barcode/printer), and localization — all built within the real constraints of entry-level shared hosting (no SSH, no Node.js/Python runtime, no Redis).
- Provide a bilingual interface so the live application is legible to both the actual store operators (Indonesian) and portfolio reviewers (English).

---

## 3. Scope

### In scope (v1)
- Single store, single physical cashier till.
- Cashier (kasir) and Owner roles.
- Product & stock management with restock alerts.
- Sales transactions with cash / QRIS / debit payment methods (manually recorded, no payment gateway API).
- Shift open/close with cash reconciliation.
- Sales & profit reporting, with PDF export.
- Barcode scanner input and receipt printing.
- Bilingual UI (English / Indonesian).

### Out of scope (v1)
- Multi-outlet / multi-branch support.
- Offline-first / sync architecture (not needed — location has stable internet).
- Payment gateway API integrations (QRIS/debit are recorded manually at checkout).
- Inventory forecasting, demand prediction, or supplier management automation.
- Native mobile app (web app only, used on a desktop/laptop till).

### Assumptions
- Stable internet connection at the store.
- Owner acts as the primary system administrator.
- One till, one active shift at a time.

---

## 4. User Roles

| Role | Access |
|---|---|
| **Owner** | Full access: product/stock management, user management, all reports, all shifts |
| **Cashier (Kasir)** | Transaction processing, own shift open/close, own shift history only |

---

## 5. Core Features

### 5.1 Sales Transaction (Point of Sale)
- Add items to cart via barcode scan or manual product search.
- Calculated subtotal, total, and change (for cash payments).
- Payment methods: Cash, QRIS, Debit (recorded manually — no gateway integration).
- Print receipt on completion.
- Void/refund a transaction, restricted to Owner approval.
- Each completed sale automatically decrements stock in the same database transaction (atomic — no partial states).

### 5.2 Stock Management
- CRUD for products: SKU, barcode, name, category, cost price, sell price, stock quantity, reorder threshold, product image.
- Stock automatically decreases on sale and increases on restock.
- Stock movement log for every change (`sale`, `restock`, `adjustment`) with reference to the originating transaction.
- Low-stock alert when `stock_qty` falls to or below `reorder_threshold`, surfaced on the Owner dashboard.

### 5.3 Multi-user & Role-Based Access
- Authentication via Laravel Breeze.
- Role and permission management via `spatie/laravel-permission`.
- Route- and UI-level enforcement: Cashiers cannot access product management, user management, or other cashiers' shift data.

### 5.4 Shift Management
- Cashier opens a shift by entering starting cash.
- All sales during the shift are tied to that shift record.
- On shift close, cashier enters actual counted cash; system calculates the expected cash and flags any discrepancy.
- Shift history viewable by Owner for all cashiers, and by each Cashier for their own shifts only.

### 5.5 Reporting & Profit Analysis
- Daily and custom date-range dashboard: total sales, profit margin, transaction count, best-selling products.
- Charted trends (sales over time, category breakdown) via Chart.js.
- Shift reconciliation report (expected vs. actual cash, per cashier).
- PDF export of any report via `barryvdh/laravel-dompdf`.

### 5.6 Hardware Integration
- **Barcode scanner**: standard USB/Bluetooth HID scanner, captured via a timing-based keystroke listener (Alpine.js) — no scanner-specific driver or library required.
- **Receipt printer**: thermal printer support via WebUSB + ESC/POS commands (client-side, browser-based, no backend dependency), with a CSS `@page`-based print-dialog fallback for simpler setups.

### 5.7 Product Image Management
- Images stored on local disk (`public/uploads/products`) — no external storage service required, since the hosting plan includes unlimited storage.
- Images compressed and converted to WebP via Intervention Image (pure PHP, no Node.js build step needed) before saving.
- Weekly offsite backup of all files is provided at the hosting level (no application-level backup logic needed for v1).

### 5.8 Localization (Bilingual UI: English / Indonesian)
- Locale switcher in the main navigation (e.g., a flag/dropdown toggle for EN / ID).
- **Default locale: Indonesian** (primary operational language for store staff); English available as a secondary option for portfolio review purposes.
- Locale preference persisted per user account (`users.locale` column); falls back to a session-based locale for guest/unauthenticated views (e.g., login page).
- All interface strings — labels, buttons, validation messages, navigation, email/notification text — are sourced from Laravel translation files (`lang/en`, `lang/id`). No hardcoded user-facing strings in Blade/Livewire views.
- The receipt template respects the active locale at the time of the transaction.
- Currency always displays in IDR (Rp) regardless of interface language — only interface text is translated, not currency formatting.
- Dates are formatted per locale (e.g., "20 Juni 2026" vs. "June 20, 2026") using Carbon's locale-aware formatting.

---

## 6. Daily Operational Workflow

1. **Open shift** — Cashier logs in, starts a shift, enters starting cash.
2. **Process sale(s)** — Scan or search product → cart → select payment method → confirm → stock auto-decrements → receipt prints. Repeated per customer.
3. **Stock check** — System flags any product at or below its reorder threshold; surfaced to Owner.
4. **Close shift** — Cashier enters actual cash counted; system computes expected vs. actual and records any discrepancy.
5. **Reporting** — Owner views the daily dashboard: sales, profit, top products, and shift reconciliation.

---

## 7. Deployment Workflow (Shared Hosting, No SSH)

1. **Develop locally** — `composer install`, `npm run build` (Vite) run entirely on the local machine.
2. **Package** — Zip the project, including the `vendor/` directory and the built `public/build` assets (the server cannot run Composer or npm).
3. **Upload** — Transfer via FTP or cPanel File Manager (no `git pull` on the server unless cPanel Git Version Control is confirmed available on this plan).
4. **Run migrations** — Use a temporary `deploy.php` script (calling `Artisan::call('migrate')`) accessed once via browser, then deleted immediately.
5. **Verify** — Confirm the application loads correctly, check `.env` configuration, and confirm file permissions on `storage/` and `public/uploads/`.

---

## 8. Data Model

```
users
  id, name, email, password, role (owner|cashier), locale (en|id, default 'id'), timestamps

products
  id, sku, barcode, name, category, cost_price, sell_price, stock_qty,
  reorder_threshold, image_path, is_active, timestamps

shifts
  id, cashier_id (FK users), opened_at, closed_at,
  starting_cash, cash_expected, cash_actual, status (open|closed), timestamps

sales
  id, shift_id (FK shifts), cashier_id (FK users), total,
  payment_method (cash|qris|debit), paid_amount, change_amount,
  status (completed|voided), created_at

sale_items
  id, sale_id (FK sales), product_id (FK products),
  qty, unit_price, cost_price_snapshot, subtotal

stock_movements
  id, product_id (FK products), type (sale|restock|adjustment),
  qty_change, reference_id, note, created_at
```

> `cost_price_snapshot` on `sale_items` preserves historical accuracy: if a product's cost price changes later, past transactions still reflect the cost at the time of sale.

---

## 9. Technical Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend framework | Laravel 11 | |
| Frontend | Blade + Livewire + Alpine.js | Minimal JS footprint; no Node.js needed at runtime |
| Styling | Tailwind CSS | Built locally via Vite; compiled assets uploaded |
| ORM | Eloquent | |
| Database | MariaDB | Included in hosting plan, unlimited |
| Auth & roles | Laravel Breeze + `spatie/laravel-permission` | Owner / Cashier roles |
| Cache & session | `file` or `database` driver | Replaces Redis (unavailable on this plan) |
| Queue | `database` driver, or synchronous for v1 | Single-till volume does not require async processing |
| Image storage | Local disk (`public/uploads/products`) | Avoids `storage:link` symlink (unreliable without SSH) |
| Image processing | Intervention Image | Pure PHP, uses GD extension |
| PDF generation | `barryvdh/laravel-dompdf` | Pure PHP, no external binaries |
| Charts | Chart.js (via CDN) | No Node.js build required |
| Localization | Laravel built-in i18n (`lang/en`, `lang/id`) | |
| Cron | cPanel Cron Jobs → `php artisan schedule:run` | |
| Barcode input | Timing-based keystroke listener (Alpine.js) | Framework-agnostic, no scanner SDK |
| Receipt printing | WebUSB + ESC/POS, CSS print fallback | Client-side, browser-based |
| Deployment | Local build → zip → FTP/File Manager upload | No SSH on this hosting tier |
| Hosting | Shared hosting (cPanel) | ~Rp 17,900/month |
| Backup | Weekly offsite backup | Included in hosting plan |

---

## 10. Non-Functional Requirements

- **Performance**: must comfortably handle at least 500 transactions/day on entry-level shared hosting resources.
- **Security**: bcrypt password hashing (Laravel default), CSRF protection, Form Request validation, role-based middleware on all protected routes.
- **Reliability**: atomic DB transactions for all stock-affecting operations to prevent partial/inconsistent states.
- **Maintainability**: standard Laravel conventions; no hardcoded user-facing strings (all routed through localization files from day one).
- **Hosting constraints**: no SSH, no Node.js/Python runtime on the server, no Redis — all build steps happen locally before upload.

---

## 11. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Hosting ToS may restrict commercial use (as seen with platforms like Vercel Hobby) | Confirm the specific shared hosting provider's ToS explicitly permits commercial/business sites before going live |
| No SSH complicates deployment and migrations | Build-locally-then-upload workflow; temporary `deploy.php` script pattern for migrations |
| Shared hosting has limited CPU/memory under contention | Efficient indexed queries, `file`/`database` cache drivers, lightweight Livewire components |
| Single server, no redundancy | Weekly offsite backup (hosting-provided); ability to redeploy quickly from local project copy |

---

## 12. Implementation Roadmap

| Phase | Scope |
|---|---|
| **1. Foundation** | Laravel setup, Breeze auth, roles/permissions, database schema & migrations, localization scaffolding (`lang/en`, `lang/id`) |
| **2. Product & Stock** | Product CRUD, image upload + compression, stock movement log, restock alerts |
| **3. POS Transaction** | Livewire cart, checkout flow, barcode listener, shift open/close, atomic stock deduction |
| **4. Hardware Integration** | WebUSB ESC/POS receipt printing, CSS print fallback |
| **5. Reporting** | Sales/profit dashboard, Chart.js visualizations, PDF export, shift reconciliation reports |
| **6. Localization Pass** | Full EN/ID translation coverage, locale switcher UI, per-user locale persistence |
| **7. Deployment** | Local build & packaging, `deploy.php` migration script, go-live verification checklist |

> Following the established pattern from other projects: each phase should be run as a separate, scoped Claude Code session with explicit "do not touch" file boundaries, beginning with "Read PRD.md first, then complete this task."

---

## 13. Open Questions

- Confirm whether the chosen shared hosting provider's ToS explicitly allows commercial use.
- Confirm whether cPanel Git Version Control is available on this plan (would simplify the deployment workflow significantly if so).
- Confirm GD extension (or an equivalent) is enabled by default on the hosting PHP configuration, required for Intervention Image.
