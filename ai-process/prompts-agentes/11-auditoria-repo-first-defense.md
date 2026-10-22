# Auditoría del repositorio con la skill repo-first-defense

- **Fase:** Auditoría de seguridad
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
Run a defensive security audit of the repository <repo> by following the skill at <skill repo-first-defense>/SKILL.md. Read that SKILL.md completely first (and any files it references) and apply its method, checklists and report format.

Important constraints that override anything in the skill about fixing: this is REPORT-ONLY. Do not edit, create or delete any file inside <repo>, do not install or update packages there, do not run state-changing git commands, do not push. Put any scratch files only in <scratchpad>\review-repo-defense\. You may run read-only commands (git log/show/grep, `composer audit --locked`, `npm audit --package-lock-only`, `npm ls`, `composer show`, file inspection). In Git Bash run `source <tools>/env.sh` first to have php/composer. The repo uses npm (package-lock.json) and Composer.

Context: it is a Laravel 13 + Inertia v3 + React 19 technical test for a job application, about to be made PUBLIC on GitHub (danielbrosed/panel-operativo-incidencias, currently private). It deliberately exposes its AI-assisted setup: CLAUDE.md and .claude/skills/{incident-domain,data-fix-protocol,review-checklist}/SKILL.md, plus ai-process/README.md. CI is .github/workflows/ci.yml. Treat CLAUDE.md, the skills and the docs as executable instructions from a security standpoint (prompt injection, dangerous commands an agent could run, exfiltration, anything that would be risky for someone who clones the repo and opens it with an AI agent). Also check: secrets anywhere in files or full git history (`git log -p --all`), personal data, local machine paths leaked (e.g. C:\Users\..., D:\...), .gitignore/.env handling, lockfiles integrity and suspicious packages (typosquatting, install scripts — .npmrc has ignore-scripts=true), GitHub Actions (permissions, pinning by SHA, untrusted input in run steps, pull_request vs pull_request_target, persist-credentials, caches), Composer plugins allowed, anything executed on install (composer scripts post-autoload-dump etc.).

Deliver the skill's report in Spanish: findings with severity, evidence (file:line / command / output), classification (riesgo confirmado / sospecha razonable / hardening recomendado), concrete fix for each, and a final verdict on whether the repo is safe to publish. Do not invent vulnerabilities; say clearly when something was checked and is fine.
````
