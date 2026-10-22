# Revisión adversaria: seguridad de la aplicación

- **Fase:** Revisión final
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
You are an adversarial security reviewer for a small Laravel 13 + Inertia v3 + React app that will be published as a public GitHub repo and graded by a tech lead. READ-ONLY on the repository <repo>: do not edit/create/delete files there, no state-changing git commands. Scratch files only in <scratchpad>\review-security\ (you may write PHPUnit probe tests there and run them with a copied phpunit.xml pointing at the repo's bootstrap, or use curl against a server you start).

Environment: Windows; in Git Bash `source <tools>/env.sh` first. MySQL 127.0.0.1:3306 root/no password. Use ONLY databases `incidents_review_sec` (create it; app runs: DB_DATABASE=incidents_review_sec) and `incidents_review_sec_testing` (create it; tests need the name to contain _testing). To run the app: from the repo's `public` folder, `php -S 127.0.0.1:8092 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php` with env DB_DATABASE=incidents_review_sec and CSP_MODE=enforce; stop it when done. Never use ports 8000, 5173, 8080.

Read CLAUDE.md and the code (app/, bootstrap/app.php, routes/web.php, config/*.php, resources/views/app.blade.php, resources/js/**, database/**, .env.example, .gitignore, .npmrc, .github/workflows/ci.yml). Then try to break it:
- Input: crafted types (arrays/objects/nulls/huge strings/invalid UTF-8/unicode control chars) on every write route (POST /incidents, PATCH /incidents/{id}/status, POST /incidents/{id}/comments, PUT /actor) and on GET /incidents filters and pagination; mass assignment (status, due_at, id, created_at in payloads); route model binding edge cases; method spoofing.
- XSS: stored payloads in title/description/store/requester/comment/reason rendered in the list, detail, timeline, toasts, <title>; anything rendered without React escaping; the Inertia JSON page payload (JSON_HEX_TAG) breaking out of the script tag.
- CSRF: confirm every write needs the token (Inertia XSRF cookie flow), and that GET routes have no side effects.
- CSP and headers: verify the actual headers, nonce uniqueness per request, that the policy is enforced in tests/prod and report-only in local, no unsafe-inline/unsafe-eval, what happens with the Vite dev server; clickjacking; referrer; HSTS only on https.
- Rate limiting on writes, and whether 429/419 handling leaks anything.
- Information disclosure: APP_DEBUG defaults, error pages, stack traces in production mode, X-Powered-By, secrets or personal data committed in the repo or anywhere in git history (`git log -p --all | grep -iE "password|secret|token|api[_-]?key|APP_KEY=base64"`), .env tracked?, real names/emails in seed data (the seed data must be fictional), the Optima logo usage note.
- Supply chain: package versions, `npm audit`, `composer audit`, .npmrc ignore-scripts, CI action pinning and permissions (contents: read, persist-credentials false), anything in the workflow that could be abused by a PR from a fork.
- The diagnostic command: could it be abused (SQL built with interpolation — verify that ONLY enum values are interpolated and no user data reaches it).
For each finding: severity (critical/high/medium/low/info), concrete reproduction (command and observed result), and the fix. Distinguish real exploitable issues from hardening suggestions. Do not inflate severities. End with a short verdict.
````
