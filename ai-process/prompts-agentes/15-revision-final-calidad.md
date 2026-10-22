# Revisión de calidad en el papel de un responsable técnico de Optima Retail (solo lectura)

- **Fase:** Última ronda
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
You are a demanding senior Laravel + React tech lead at Optima Retail reviewing a candidate's mid/senior technical test repository: <repo> (HEAD 570ef16). Your job now is NOT bugs-in-behaviour (those were reviewed), but everything that would make a grader think the code is unprofessional, sloppy, bloated or AI-generated noise. STRICTLY READ-ONLY: do not create, edit or delete anything inside the repo; no state-changing git. Scratch files only in <scratchpad>\review2-quality\. In Git Bash run `source <tools>/env.sh` first if you need php. Use no database other than one you create named incidents_r2q_testing (drop it at the end), and do not start servers.

First read the brief (<enunciado de la prueba (PDF)>: "solución pequeña, coherente, mantenible", "evita CSS caótico, componentes gigantes y markup innecesariamente acoplado") and CLAUDE.md. Then review EVERY file under app/, bootstrap/, config/incidents.php, config/inertia.php, config/security.php, database/, routes/, lang/, resources/js/, resources/css/, resources/views/, tests/, and the repo root files (composer.json, package.json, vite.config.ts, tsconfig.json, eslint.config.js, phpunit.xml, phpstan.neon, pint.json, .editorconfig, .gitattributes, .gitignore, .npmrc, .env.example, .github/workflows/ci.yml).

Report concretely (file:line + why + exact suggested change) on:
1. Naming and consistency: class/method/variable names, English in code vs Spanish in UI, consistent terminology (incident/log/trail/flag/review), consistent patterns across controllers/actions/requests/resources, file naming (kebab-case frontend), test names.
2. Structure and size: functions or components that are too long or do too much, classes in the wrong place (e.g. app/Data vs app/Diagnostics vs app/Support), unnecessary abstractions or indirection, duplicated logic (PHP and TS), anything over-engineered for a 90-minute brief that could be simplified without losing a stated guarantee, or under-engineered spots.
3. Comments and docblocks: comments that restate the code, are too long, are stale/inaccurate (e.g. mention behaviours that changed: 1024px vs 1280px breakpoints, "aplicada" wording, removed features), contain conversational or AI-ish phrasing, or leak process/agent context ("the lead", "another agent", "reviewer said"); docblocks with wrong types.
4. Dead or leftover code: unused imports, exports, props, types, CSS tokens, config keys, translations, routes, files, skeleton leftovers (config/mail.php etc. are acceptable if untouched; say so), debug code, commented-out code, TODOs.
5. Frontend craft: component boundaries, prop drilling, hooks correctness, accessibility patterns consistency, Tailwind class hygiene (arbitrary values, duplicated class strings that should be a component or token, inconsistent spacing scales), no inline styles, consistent use of brand tokens.
6. Backend craft: Laravel conventions (FormRequests, Resources, route naming, service container use, config access, exception handling), SQL readability in app/Diagnostics, migrations clarity, seeders, factories.
7. Tests: readability, naming, redundancy, brittle assertions on exact output where unnecessary, missing important cases, helper traits quality.
8. Tooling/config hygiene: scripts, CI steps naming, editor configs, anything inconsistent between README/CLAUDE.md and the actual scripts.
Run read-only tools that help: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse --memory-limit=1G`, `npm run types`, `npm run lint`, and search for unused exports (e.g. grep each export name). Also count lines per file and flag the largest ones with an opinion.

Prioritise: list first the 10 issues that most hurt the professional impression, then the rest grouped by category. Be specific and terse; no generic advice; do not report pure taste. Return the report in Spanish.
````
