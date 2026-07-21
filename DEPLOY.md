# IWK NBS — Completion Demonstration Prototype (Laravel)

A deployable Laravel 11 application demonstrating IWK's New Billing System in two
states — **Version 1 As-Is (incomplete)** vs **Completed (proposed)** — with working
business logic on a fictitious sample dataset:

- **Receipting** — payments allocate oldest-bill-first across real bills, update
  balances and statuses, voidable with full reversal.
- **Billing runs** — tariff-rated trial (register only) and live (posts real bills).
- **Debt recovery** — aging computed live from unpaid bills; instalment
  arrangements generate real schedules.
- **Enquiries** — logged with SLA, enforced status transitions, CEMS routing.
- **Forecasting** — computed from the billed history in the database, adjustable
  (IWKFCADJCALC) and freezable (IWKFCFRZ); **refused entirely in v1**, because IWK
  graded all 11 processes gap.
- **Adjustments** — post for real; in v1 the server refuses summary adjustments
  (IWKSUMADJ — graded enhancement). The version gate is server-side, not cosmetic.
- **Catalogue** — all 594 Appendix 11 processes browsable, each rendered as a
  screen whose v1 behaviour follows IWK's own grading.

Stack: PHP 8.2+ · Laravel 11 · SQLite (zero external services). All data fictitious.

## Quick start (any machine with PHP 8.2+ and Composer)

```bash
unzip iwk-nbs-laravel.zip && cd iwk-nbs
composer install
cp .env.example .env
php artisan key:generate
# .env: DB_CONNECTION=sqlite and an absolute DB_DATABASE path, e.g.
#   DB_DATABASE=/full/path/to/iwk-nbs/database/database.sqlite
touch database/database.sqlite
php artisan migrate --seed        # 220 accounts · 1,540 bills · receipts via real allocation · 594 processes
php artisan serve                 # → http://127.0.0.1:8000
```

Windows (Laragon/XAMPP): same commands in the project folder; point the vhost at
`public/`.

## Docker

```bash
docker build -t iwk-nbs-demo .
docker run -p 8000:8000 iwk-nbs-demo
```

The image seeds a fresh database on start — every container boots to the same
deterministic sample dataset (seeded mt_srand), so demos are repeatable.

## Production-style serve (for a demo server)

Point nginx/Apache at `public/`. SQLite needs no DB server. Set `APP_ENV=production`,
`APP_DEBUG=false`, run `php artisan config:cache route:cache view:cache`.
**The app ships without authentication by design** (demo walkthrough); deploy behind
your own access control (VPN, basic auth, IP allowlist) if exposed.

## Resetting the demo between walkthroughs

```bash
php artisan migrate:fresh --seed
```

## Where the honesty lives (worth knowing before presenting)

- `config/nbs.php` — every gated feature traces to named Appendix 11 processes and
  IWK's grade; the gate map is the only place feature availability is defined.
- `app/Support/Nbs.php` — v1/v2 gating logic (server-side).
- `app/Services/` — the real business logic (allocation, billing, arrangements, forecast).
- Every page footer stamps the reconstruction caveat; sample data is deterministic
  and clearly fictitious.
- The billing run is deliberately simplified (flat sample tariffs) and says so on
  screen — the real engine's 30 unfinished processes are exactly the completion scope.

## Coolify

1. Push this repo to GitHub (or pull it via Coolify's GitHub app / deploy key).
2. Coolify → **New Resource → Application**, pick the repo, build pack **Dockerfile**.
3. Port: **8000** (exposed by the image). Healthcheck path: **/up**.
4. Environment variables: set **APP_KEY** once (`php artisan key:generate --show`
   locally, or let the first boot generate one and then pin it), `APP_ENV=production`,
   `APP_DEBUG=false`, and `APP_URL=https://your-domain`.
5. Optional but recommended: add a **persistent volume** mapped to `/app/database`
   so the demo data survives redeploys. Without it, every deploy boots a fresh
   seeded dataset (deterministic — which is also a feature for repeatable demos).
6. To reset the demo at any time: Coolify terminal → `php artisan migrate:fresh --seed --force`.
7. The app ships without authentication — keep it on a non-guessable domain or
   behind Coolify's basic-auth/IP rules for the evaluation period.
