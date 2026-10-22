# Plano de implementación y riesgos técnicos

- **Fase:** Planificación
- **Tipo de agente:** Plan
- **Qué aportó:** Decisiones de esqueleto, el 409 reservado por Inertia, CSP con Vite, PHP 8.3, estrategia de tests y CI.

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
You are a senior Laravel + React engineer preparing the concrete implementation blueprint for a small but very polished technical test. Read-only task: do not write files, do not install anything.

Read first:
1. Brief (PDF): <enunciado de la prueba (PDF)>
2. Draft plan (Spanish): <plan aprobado>

Verified environment facts (22-oct-2026):
- Windows 11 Home, PowerShell + Git Bash; Node 24.19, Git, gh CLI logged in; NO PHP, NO Composer, NO MySQL, NO Docker, NO WSL, no admin-free package managers except winget.
- Plan: PHP via `winget install -e --id PHP.PHP.NTS.8.4` (8.4.27, portable zip under %LOCALAPPDATA%\Microsoft\WinGet\Packages, VC++ redist already present; need to create php.ini from php.ini-development, extension_dir="ext", enable curl, fileinfo, intl, mbstring, openssl, pdo_mysql, pdo_sqlite, sqlite3, zip, sodium; maybe set PHPRC; alias may need PATH if Developer Mode is off). Composer: composer.phar from getcomposer.org + composer.bat shim. MySQL: official ZIP https://cdn.mysql.com/Downloads/MySQL-8.4/mysql-8.4.13-winx64.zip (MD5 7b94489d5524dea9b27097068bea25a8), my.ini with forward slashes, `mysqld --defaults-file=... --initialize-insecure --console`, then run in background with Start-Process hidden, bind 127.0.0.1, mysqlx=OFF; DBs created with utf8mb4_unicode_ci.
- Current versions: Laravel 13.38 (PHP 8.3-8.5; Symfony 8 needs 8.4 → require "php": "^8.4"), Inertia v3 (inertiajs/inertia-laravel 3.5.2, @inertiajs/react 3.9.1, @inertiajs/vite 3.9.1; peer react ^19; axios removed; Page.layout must be an array for arrow layouts; useForm processing resets only in onFinish; initial page data in <script type="application/json">; config testing.ensure_pages_exist true), React 19.3, Tailwind 4.3 + @tailwindcss/vite, laravel-vite-plugin 3.2 (vite ^8), @vitejs/plugin-react 6, TypeScript: pin ^5 (npm latest is 7.0.2 released today), Pest 4.7 (PHPUnit 12) or PHPUnit 12.5 (skeleton default), Larastan 3.13, Pint 1.32. `laravel new --react --no-authentication` installs laravel/blank-react-starter-kit (Inertia v3, React 19, TS, Tailwind 4, Wayfinder, Larastan level 7, Pint, PHPUnit, CI) but it uses vite-plus (`vp` command) as build tool. The skeleton phpunit.xml forces sqlite :memory: — must switch to MySQL test DB. lockForUpdate is a no-op on SQLite. Feature tests rendering Inertia pages need the Vite manifest (build first or withoutVite()). Windows is case-insensitive: page name casing bugs only show on Linux CI. shivammathur/setup-php had CVE-2026-46420 fixed in 2.37.1 → pin by SHA. Pail needs pcntl (not on Windows) so `composer run dev` behaves differently.

Deliver a concrete blueprint:
1. Scaffold decision: plain `laravel new` (Blade, no kit) + manual Inertia v3/React/TS/Tailwind, versus the official blank React starter kit (with vite-plus/Wayfinder). Pick one for a reviewer who must run it without friction on any OS, and justify. Same for client-side routes: Wayfinder vs Ziggy vs URLs passed from the server. Same for Pest vs PHPUnit.
2. Full file tree of the final repo (only intentional files), with one line per file on its responsibility. Include config/incidents.php, enums, actions, exceptions, form requests, resources, controllers, middleware, migrations, seeders, the diagnose command and its check classes, React pages/components/types/hooks, tests, .github/workflows, ai-process/, CLAUDE.md, .claude/skills/*, README.md, .env.example.
3. Key code contracts (signatures, not full code): IncidentStatus enum API; ChangeIncidentStatus::handle(...) including lockForUpdate + expected-status conflict check + DB::transaction + domain exceptions → how they become 422/409 Inertia errors shown on the right form field; how transitions available to the UI are computed server-side and sent in the resource; how IncidentLog immutability is enforced; CurrentActor.
4. Inertia v3 specifics: app.tsx / layout wiring, partial reloads for the quick filter (`router.get` with `only`, `preserveState`, `preserveScroll`, `replace`), debounced search, counters, how errors and flash messages flow (shared props in HandleInertiaRequests), confirm dialog with native <dialog>.
5. Security hardening that is cheap and robust: CSP with nonce via Vite::useCspNonce() under Inertia v3 + Vite dev server (exact policy for prod vs local, does Inertia v3's JSON script tag need a nonce?), other headers, throttle, LIKE escaping, mass assignment. Flag anything risky to get working and recommend whether to keep it.
6. Testing strategy: which tests, how to test the rollback (force the log insert to fail), how to test the stale-status conflict, how to test the diagnose command (dry-run changes nothing, --fix, idempotency, exit codes), MySQL test DB config in phpunit.xml, Vite manifest handling.
7. CI workflow (GitHub Actions) final shape with pinned SHAs if you can determine them, MySQL 8.4 service, PHP 8.4, steps (composer validate/install, npm ci, build, pint --test, larastan, tsc, tests, migrate --seed + diagnose smoke checking expected exit codes).
8. Windows local setup: exact ordered PowerShell commands and verification checks; pitfalls (PATH refresh in the same session, php.ini location, MySQL first start, port 3306 free check).
9. Build orchestration: the work will be done by AI subagents. Propose the sequence and which parts can run in parallel without file conflicts (e.g. after the domain layer is merged: frontend / diagnostics+seeders / docs), and the review gates.
10. Risks list with mitigations.

Be concrete and concise. Return the blueprint as structured text.
````
