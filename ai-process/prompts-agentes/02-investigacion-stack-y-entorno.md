# Versiones actuales del stack e instalación en Windows

- **Fase:** Planificación
- **Tipo de agente:** Explore
- **Qué aportó:** Laravel 13, Inertia v3, React 19; PHP, Composer y MySQL sin administrador; plantilla de CI.

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
READ-ONLY research task. Do not install anything, do not write or save files. Only run read-only commands (e.g. `winget search`, `winget show`) and web research. Today is 2026-10-22.

Context: on a Windows 11 Home machine (no admin-free tools like scoop/choco; winget is available; Node 24.19 and Git are installed; NO PHP, NO Composer, NO MySQL, NO Docker, NO WSL) we must build a Laravel + MySQL + Inertia + React (TypeScript) + Tailwind app (a small technical test) and then run its test suite and the app locally. A reviewer will also clone it on their own machine (any OS), and a GitHub Actions CI workflow will run it against a MySQL service.

Find and report, with sources (official docs preferred):
1. Current stable versions as of Oct 2026: Laravel framework (is Laravel 13 out? release date, minimum PHP), recommended PHP version (8.4/8.5), Inertia.js (inertiajs/inertia-laravel and @inertiajs/react current major — v2 or v3?) and any breaking changes vs v2, React version, Tailwind CSS v4 + @tailwindcss/vite, laravel-vite-plugin, Pest vs PHPUnit default in new Laravel apps, Larastan current major, Laravel Pint. Also whether `laravel new` without a starter kit still exists ("none" option) and how to add Inertia + React manually (official Inertia server-side + client-side setup steps for the current version).
2. Best way to install PHP + Composer on Windows 11 without admin for a CLI dev workflow: evaluate (a) php.new one-liner installer for Windows (exact command from php.new / Laravel docs, what it installs and where, which PHP extensions are enabled — need pdo_mysql, mbstring, openssl, fileinfo, intl, zip, curl), (b) winget packages — run `winget search php`, `winget search composer`, `winget search mysql`, `winget search mariadb` and report exact package IDs and versions, (c) Laravel Herd for Windows (free tier: does it include MySQL? no — confirm).
3. Best way to get a MySQL 8.x (8.4 LTS preferred) server running locally on Windows 11 for development: winget ID (Oracle.MySQL? does it need admin / installs a service?), or the MySQL "noinstall" ZIP archive approach (initialize with `mysqld --initialize-insecure`, run as console process on a custom port, no service, no admin) — give the exact official download URL pattern for the 8.4 LTS Windows x64 ZIP and the commands. Recommend the most reliable path that does not need admin rights and does not leave a permanent Windows service.
4. GitHub Actions: a current, known-good example of a workflow that runs Laravel tests against a `mysql:8.4` service container with shivammathur/setup-php (current major version tag), including health-check options and the env vars Laravel needs.
5. Any known gotchas: Laravel + MySQL 8.4 default auth plugin (caching_sha2_password) with PHP pdo_mysql, utf8mb4, `lockForUpdate` in tests, JSON columns, PHP 8.4/8.5 deprecations affecting Laravel.

Return a concise, structured report with exact commands, versions and source URLs. Flag anything you could not verify.
````
