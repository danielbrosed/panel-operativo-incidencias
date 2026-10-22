# Revisión adversaria: exactitud y honestidad de la documentación

- **Fase:** Revisión final
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
You are an adversarial reviewer of documentation accuracy and honesty for a technical test repository that will be published and graded: <repo>. READ-ONLY on the repository (no edits, no state-changing git). Scratch files only in <scratchpad>\review-docs\.

Environment: Windows; Git Bash `source <tools>/env.sh` first; MySQL 127.0.0.1:3306 root/no password. You may create and use ONLY databases whose name starts with `incidents_review_docs` (e.g. `incidents_review_docs` and `incidents_review_docs_testing`). Do not start servers on ports 8000/5173/8080.

Documents to check: README.md, ai-process/README.md, CLAUDE.md, .claude/skills/*/SKILL.md, .env.example comments, code comments that make claims, and the git history (`git log --format='%h %ad %s%n%b' --date=iso`).
1. Every factual claim in README.md must be TRUE in the code or when run: commands and scripts exist and do what is said (composer scripts, npm scripts, artisan commands and options, exit codes), file paths exist, versions and requirements are right (composer.json, package.json, CI), numbers (tests and assertions count: run the suite against your own _testing DB with DB_DATABASE=incidents_review_docs_testing php artisan test; incident counts and ids after seed; SLA hours), outputs pasted in the README match a real run (run migrate:fresh --seed and incidents:diagnose, -v, --fix --force, and again, on DB_DATABASE=incidents_review_docs and diff against the README text), SQL in the README equals what -v prints, the state machine table equals app/Enums/IncidentStatus.php, the decisions described match the implementation (e.g. due_at stored, CHECK constraints, CSP modes, throttle limits, no comment logs, breakpoints, error mapping). Also check the README's internal anchors/links and image paths.
2. ai-process/README.md must be honest and verifiable: every error listed must be traceable (git history, code, tests); nothing claimed that did not happen; it must not overstate what the human did. A private process log exists at <scratchpad>\ai-log.md — compare the two. Flag any HTML comment placeholders ("AL CERRAR") that must be filled before publishing.
3. The brief (<enunciado de la prueba (PDF)>, sections 8, 9 and 10) — does the documentation satisfy them?
4. Language quality: Spanish with correct accents and punctuation, no English leftovers in user-facing text (README, UI strings in resources/js and lang/, command output strings in app/Diagnostics and app/Console), consistent terminology (incidencia, log/historial, revisión manual, motivo), no marketing tone, no emojis, length reasonable for a reviewer (suggest concrete cuts if too long).
For each finding: severity (blocker/major/minor/nit), evidence (quote + where + what is actually true) and the exact fix text. End with a short verdict.
````
