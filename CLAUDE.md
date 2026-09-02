# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**Vast Solutions** (CAPSTONE-1) is a PHP/XAMPP web system for a custom cabinet-manufacturing business. It runs as vanilla PHP served by Apache from `C:\xampp\htdocs\CAPSTONE-1` — there is no framework, no build step, and no package manager. Pages are `.php` files that mix server logic with HTML; the frontend uses Bootstrap 5 + Bootstrap Icons via CDN and hand-written CSS (`admin/admin.css`, `user-dashboard/dashboard.css`, `style/`).

The app has two areas: an **admin/staff** back office (`admin/`) and a **client** portal (`user-dashboard/`). The public marketing site is `index.php`, with `login.php` / `signup.php` as the (not-yet-wired) entry points.

## Running & testing

- **URL base:** `http://localhost/CAPSTONE-1/...` (Apache serves the htdocs folder directly — no server to start beyond XAMPP).
- **PHP CLI:** `C:\xampp\php\php.exe` (v8.0.30). Lint with `php -l <file>`.
- **No test suite exists.** Verify flows by driving endpoints over HTTP.
- **Identity for testing:** hit any page with `?dev_user=admin` or `?dev_user=client` on the first request; the choice persists in the session (see DEV_MODE below). Seeded logins: `admin@vastsolutions.com` / `admin123`, `client@demo.test` / `client123`.

### Environment gotchas (Windows/XAMPP box)

- **`mysql.exe` CLI is broken** here (missing `caching_sha2_password.dll`). Do DB work through PHP (`php.exe -r 'require "includes/db.php"; ...'`) or phpMyAdmin — not the mysql client.
- **`curl -F "field[]=@file"` crashes the connection** (HTTP 000) — array-bracket multipart field names break this curl/Apache build. The server itself handles them fine (real browsers work). To test array uploads like `design_files[]`, build a raw multipart body and send with `--data-binary @body`. Single-field uploads (`-F "cutfile=@x"`) work.
- Apache occasionally restarts mid-session (brief HTTP 000 on everything) — re-check `index.php` and retry.

## Architecture

### Shared includes (`includes/`)

Every page and endpoint is built on these. Read them before touching backend logic:

- **`db.php`** — `db()` returns a shared PDO to MySQL `vast_solutions` (127.0.0.1, root, no password; exceptions on, assoc fetch, real prepares).
- **`auth.php`** — session bootstrap + `current_user()`, `current_role()`, `require_login()`, `require_role()`. See DEV_MODE below.
- **`helpers.php`** — used by every write endpoint: `next_code($prefix)` (generates `REQ-/QT-/PRJ-YYYY-NNN` codes), `log_audit()`, `notify()`, `add_project_update()` (timeline entry + client notification), `peso()`, `time_ago()`.
- **`project_status.php`** — the single source of truth for project status vocabulary (10 ordered phases + `on_hold`/`rejected`). All labels, badge CSS classes, and step indices come from here, and `project_status_js()` emits the same vocabulary to JS so client-side rendering can't drift. Never hard-code a status string or badge class — route it through these functions.
- **`material_totals.php`** — dependency-free BIFF8 `.xls` reader for Cabinet Vision "Material Summary" exports (parses the OLE container + BIFF records directly). `parse_material_totals()`.
- **`cutlist_processor.php`** — summarizer for `.cut` cutting-list files (CSV), ported from the legacy VFP "CVProcess" tool; routes rows by `opti` code into wood/alu/hardware and dedupes.
- **`notif_bell.php`** — shared notification-bell UI fragment.

### DEV_MODE (important)

`auth.php` defines `const DEV_MODE = true`. While true, the system **auto-logs-in** a seeded user (admin by default, or client via `?dev_user=client`), and `require_login()`/`require_role()` are no-ops — so every page is reachable without signing in. Real login/signup handlers are **not built yet**; wiring them and flipping `DEV_MODE = false` is intended as the final step. Don't assume auth is enforced while developing.

### Data model & lifecycle

MySQL `vast_solutions`, 16 tables: `users`, `customers`, `project_requests`, `request_files`, `projects`, `quotations`, `quotation_items`, `project_updates`, `project_materials`, `summ_batches`, `summ_items`, `material_library`, `edging_library`, `audit_logs`, `notifications`, `company_settings`. Schema lives in `database/vast_solutions.sql`.

Two status fields drive the whole app:

- **`quotations.status`**: `Sent` → `Accepted` (by client) → `Approved` (by admin) / `Rejected`.
- **`projects.status`**: `quote_submitted` → `approved` → `production` → … → `completed`, plus off-track `on_hold` / `rejected` (see `project_status.php`).

**Quote → project flow** (each step is its own endpoint; every write calls `log_audit()` + `notify()`):

1. `user-dashboard/request_quote.php` / `submit_quote.php` — client submits a request.
2. `admin/create_quotation.php` — admin builds the quote, importing material totals via `import_material_totals.php` / `material_totals.php`.
3. `user-dashboard/accept_quote.php` / `reject_quote.php` — client decides.
4. `admin/update_quotation.php` — admin approves → project becomes `approved`.
5. `admin/mark_ready.php` — moves project to `production` and copies `summ_items` into `project_materials`.
6. `admin/update_project_status.php` / `post_update.php` — ongoing monitoring updates.

### Client-visibility rule

The client portal (`my_projects.php`, `project_detail.php`, `download_quote.php`) shows only the quotation **total** — never the internal material/costing line items. Keep internal breakdowns (summarization, costing, cutlists) inside `admin/` only.

### Conventions

- Pages live one level deep and include shared files via `require_once __DIR__ . '/../includes/...'`.
- Endpoints are named for their verb (`save_*`, `update_*`, `mark_*`, `export_*`, `download_*`, `process_*`, `import_*`) and generally handle a POST then redirect or emit JSON.
- All DB access goes through PDO prepared statements from `db()`.
- `admin/library_api.php` backs material/edging library lookups (the Edge/edging library is intentionally left empty so raw edge codes match legacy output).
