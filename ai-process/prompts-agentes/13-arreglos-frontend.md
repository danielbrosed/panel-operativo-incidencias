# Arreglos del frontend tras la revisión

- **Fase:** Arreglos
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
You are fixing frontend findings in a Laravel 13 + Inertia v3 + React 19 + TypeScript + Tailwind 4 technical test at <repo> (an incident panel branded for Optima Retail). First read: <repo>\CLAUDE.md, .claude\skills\review-checklist\SKILL.md, then all of resources\js\** and resources\css\app.css. Before using any Inertia API, check node_modules\@inertiajs\react\types and node_modules\@inertiajs\core\types (v3: events are 'httpException' and 'networkError'; there is no axios).

An independent reviewer reported these (its scripts and screenshots: <scratchpad>\review-frontend\ — reuse its puppeteer scripts to verify):

MAJOR
1. The table cuts off the Actions column between 1024 and ~1195 px (incident-list.tsx switches to the table at `lg:`; the list card is overflow-hidden; the table is ~1146 px wide). Fix: switch table/cards at `xl:` (1280 px) and also wrap the <table> in a div with `overflow-x-auto` as a safety net; fix the stale comment ("~1000px").
2. Each StatusActions renders its own ConfirmDialog and every row is rendered twice (table + cards switched with display:none). Opening a dialog and then crossing the breakpoint leaves an open dialog inside a display:none subtree: the page becomes inert. Fix: render the dialog through `createPortal(…, document.body)` so it never sits inside a hidden subtree (keep it accessible: aria-labelledby, focus handling, Esc). Verify by opening "Bloquear" at 900 px and resizing to 1300 px: the dialog must stay visible and usable, and after closing it the page must be interactive.
3. (Backend part already done by the lead in bootstrap/app.php: on 419/429 Inertia requests now get a redirect back WITH a validation error on the key `request` plus the error toast.) Your part: make sure every form (create-incident-form, status-actions dialog/direct transitions, comment-form, actor-switcher) does NOT reset, close or lose typed text when that happens (onError path), and that the user sees the message (the toast already shows it; inside the open dialog also show `errors.request` if present). Verify by intercepting the POST/PATCH in puppeteer and responding with a 303 to the page while the server session has that error — or simply by setting the `writes` limiter low is not allowed (backend is not yours); intercepting is fine.

MINOR
4. Network errors: offline clicks do nothing and log uncaught HttpNetworkError. Handle router.on('networkError') (preventDefault + error toast "Sin conexión con el servidor. Comprueba la red y vuelve a intentarlo.").
5. A 500 shows Inertia's raw error modal, which the enforced CSP leaves unstyled and in English. Handle router.on('httpException') for 5xx (and other non-Inertia responses): preventDefault + error toast "Algo ha fallado en el servidor. Vuelve a intentarlo en un momento." — but keep the default behaviour in local debug if you think it matters for developers (decide and comment briefly).
   Put 4 and 5 in one place (e.g. the toaster or a small hook used by the layout) so the toast system stays single-sourced.
6. A page number past the end (?page=5) shows "Todavía no hay incidencias…" while 17 exist. When meta.total > 0 and data is empty, show "Esta página no tiene incidencias." with a link to page 1 (keeping the filters).
7. Errors inside the confirm dialog (reason missing) and the comment form are not focused or announced: focus the invalid field in onError (as create-incident-form's focusFirstError does) and make sure the error text is associated (aria-describedby) and announced.
8. Focus fallback: when the changed row leaves the filtered list, after a stale rejection, or after creating an incident, focus drops to <body>. Add a sensible fallback target (e.g. the list heading or list region with tabIndex=-1).
9. The stale/"ya está" message under a row never goes away: clear the form errors when the incident's status value changes.
10. Field borders fail WCAG 1.4.11 (#e4e4ea on white 1.27:1). Add a token for control borders in resources/css/app.css (e.g. --color-control: #878596, ≥3:1 on white and on #f6f6f6) and use it for inputs/selects/textareas/checkbox-like controls; keep `line` for dividers. Check the computed ratios.
11. The list dims (opacity 0.6) on every filter change even for fast responses: delay the dim ~150 ms like the progress bar.
12. "Volver al listado" drops filters and page: remember the last list URL (sessionStorage in a try/catch, written by the list page) and use it for the back link, falling back to urls.index.
13. Toasts: pause auto-dismiss while hovered or focused; error toasts use role="alert" and stay until dismissed (with a close button); success toasts stay polite and auto-dismiss.

NITS (do them, they are small): remove `aria-disabled` from the non-interactive <span> in pagination (or make it meaningful); on the detail page use a plain div instead of <aside> for the actions/data column if it is the main content; mark "Responsable" as optional like "Tienda"; fix stale comments (button.tsx mentions links that don't exist); the red overdue bar on rounded cards should not be clipped into a curve (e.g. inset it or use a left border inside the padding).

Constraints:
- You own ONLY resources\js\** and resources\css\app.css. Do not touch PHP, routes, config, README, docs, ai-process, tests or migrations (other people are editing them right now). If something needs backend support beyond what is described, write it in your report instead of doing it.
- UI text in Spanish with correct accents; code and comments in English; comments sparse; components small; no new dependencies; no dangerouslySetInnerHTML; kebab-case lowercase file names; brand tokens only.
- Environment: Windows; Git Bash `source <tools>/env.sh` first. MySQL 127.0.0.1:3306 root/no password. Use ONLY the database `incidents_review_fe` (re-seed with `DB_DATABASE=incidents_review_fe php artisan migrate:fresh --seed`; if migrations fail because another agent is mid-edit, wait a minute and retry). Serve from the `public` folder: `php -S 127.0.0.1:8093 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php` with env DB_DATABASE=incidents_review_fe, CSP_MODE=enforce and CACHE_STORE=array. Never use ports 8000, 5173 or 8080, and stop your server at the end. Puppeteer is global (<puppeteer global> via createRequire); Chrome at <Chrome>. Put scripts/screenshots only in the review-frontend scratch folder above.
- `npm run build` writes public/build, which a preview server on port 8080 also serves: that is fine and expected; run it at the end.
- No git commits.

Verify before finishing: npm run types, npm run lint, npm run format then format:check, npm run build; re-run the reviewer's checks for 1, 2, 3, 6, 7, 9 and a page-check (<scratchpad>/qa/page-check.mjs <url> <outDir> <label>) on /incidents and /incidents/8 at 1440, 1100 and 375 px with CSP enforced: zero console errors, zero CSP violations, no horizontal overflow, no cut-off controls. Also run axe-core again if you can (install it only in the scratch folder).

Return a concise report: what changed per finding (file:line), verification commands and results, and any AI mistakes you made and caught (honest; empty if none).
````
