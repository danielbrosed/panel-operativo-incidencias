# Revisión adversaria: frontend, Inertia y accesibilidad

- **Fase:** Revisión final
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
You are an adversarial frontend reviewer (senior React + Inertia + accessibility) for a Laravel 13 + Inertia v3 + React 19 + TypeScript + Tailwind 4 technical test, branded for Optima Retail. READ-ONLY on the repository <repo>: do not edit/create/delete files there; no state-changing git. Scratch files (puppeteer scripts, screenshots) only in <scratchpad>\review-frontend\. Existing QA helpers you can read and reuse: ...\scratchpad\qa\page-check.mjs, interactions.mjs, focus.mjs.

Environment: Windows; Git Bash `source <tools>/env.sh` first. MySQL 127.0.0.1:3306 root/no password. Create and use ONLY the database `incidents_review_fe` (`DB_DATABASE=incidents_review_fe php artisan migrate:fresh --seed`). Serve the built app (public/build already exists; you may run `npm run build` — it writes public/build which is shared, so only do it if the build is missing) from the `public` folder with `php -S 127.0.0.1:8093 ..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php` and env DB_DATABASE=incidents_review_fe, CSP_MODE=enforce. Never use ports 8000, 5173, 8080; stop your server at the end. Puppeteer global (<puppeteer global> via createRequire), Chrome at <Chrome>.

Read CLAUDE.md, .claude/skills/review-checklist/SKILL.md, the brief's Ej. 1 and Ej. 4 (<enunciado de la prueba (PDF)>), README sections about the frontend, then all of resources/js/**, resources/css/app.css, resources/views/app.blade.php. Review:
1. Architecture: is it clear where state lives, where actions fire and how the UI refreshes (the brief asks exactly this)? Any duplicated sources of truth, logic that belongs on the server, components too big (> ~150 lines) or too coupled, dead code, unused exports/props/tokens, misleading comments, inconsistent naming, any (TypeScript `any` / non-null assertions) abuse.
2. Inertia v3 correctness: check against node_modules/@inertiajs/react/types and @inertiajs/core/types — partial reloads (`only`), preserveState/replace, errorBag usage, useForm lifecycle (processing/onFinish), flash event handling, Link usage, layout default, progress nonce. React 19 StrictMode effects (double-invocation), stale closures, missing cleanup, race conditions in the debounced search (typing while a response arrives), keys.
3. Real behaviour in Chrome at 1440 and 375 px: run through filters/counters (no full reload), search, pagination, create with errors, every transition including confirm dialog and reason, stale conflict across two tabs, reopen, comments, actor switch, toasts, empty state. Report any bug, console error, CSP violation, layout break, overflow, flicker, focus loss.
4. Accessibility (WCAG 2.1 AA): run axe-core if you can load it from node_modules (not installed — you may `npm i axe-core` into YOUR scratch folder only, never in the repo) or check manually: landmarks, heading order, labels, aria-pressed/expanded/busy/live usage, dialog semantics and focus management, keyboard-only flow, colour contrast of text and of status/priority badges and buttons (compute ratios), reduced motion, zoom 200%.
5. Brand fidelity vs optimaretail.eu (gradient #3417FF→#0086FF, navy #0C0233, #F6F6F6 background, pill buttons, 30px cards, fields rounded only on the right, Montserrat as Gotham substitute) and overall visual polish/consistency.
For each finding: severity (blocker/major/minor/nit), evidence (file:line, screenshot path, measured value) and a concrete fix. End with a short verdict.
````
