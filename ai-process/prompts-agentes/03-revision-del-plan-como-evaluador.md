# Revisión del plan desde el papel de quien corrige la prueba

- **Fase:** Planificación
- **Tipo de agente:** Plan
- **Qué aportó:** Requisito que faltaba (cambio de estado en la lista), recortes por exceso y la regla única de corrección del diagnóstico.

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
You are a senior Laravel tech lead at a Barcelona company (Optima Retail: facility management for retail chains; their client portal "Optima Online" is a Laravel app) and you will GRADE a mid/senior technical test. Read-only task: do not write files.

Read these two files fully first:
1. The test brief (PDF): <enunciado de la prueba (PDF)>
2. The candidate's draft implementation plan (Spanish): <plan aprobado>

Fixed decisions (do not argue them): Laravel 13 + PHP 8.4 + MySQL 8.4, Inertia v3 + React 19 + TypeScript + Tailwind 4; no auth; public GitHub repo; AI usage shown openly (/ai-process + CLAUDE.md + project skills); no AI feature inside the app; UI/README in Spanish, code in English.

Produce a critique and refinement focused on what a grader of THIS brief rewards or penalises:
A. Rubric coverage: go through every bullet of exercises 1-5, section 8 (ai-process), 9 (delivery) and 10 (README minimum) and say whether the plan covers it, and anything missing or ambiguous. Be literal: e.g. "Registrar en incident_logs cada cambio de estado, incluyendo valor anterior, valor nuevo, usuario o actor y fecha"; "Transiciones mínimas: abierta -> en revisión -> bloqueada o resuelta"; "resuelta no debería volver a abierta sin log explícito de reapertura"; "al menos tres inconsistencias: resuelta sin log de resolución, estado que no coincide con su último log, log duplicado o incoherente"; "dry-run"; "documenta la query".
B. Over-engineering risk: the brief says ~90 minutes, "solución pequeña, coherente, mantenible"; "la diferencia está en el criterio técnico, no en la cantidad de pantallas". Which planned items would make a grader think "bloated / AI-generated bulk" and should be cut or simplified (e.g. CHECK constraints, CSP with nonce, voided_at columns on logs, flagged_for_review_at column, store/due_at extra columns, Larastan, 4th diagnostic check, CurrentActor operator selector, Wayfinder/Ziggy)? For each, keep / simplify / cut, with a one-line reason. Keep what demonstrates senior judgement cheaply.
C. State machine: validate the transitions (open→in_review; in_review→blocked|resolved; blocked→in_review; resolved→open with mandatory reason and action 'reopened'). Should blocked→resolved or in_review→open be allowed? Should a reason be required for blocking/resolving? Decide and justify briefly.
D. Exercise 5 design, concretely: write the actual MySQL 8 SQL for each check (resolved without resolution log; status ≠ latest status log; duplicate logs; incoherent log chain), stating which actions count as "status logs" (created, status_changed, reopened, reconciled), how voided/duplicate logs are excluded, precedence between overlapping checks (an incident resolved with no resolution log will also mismatch its last log — which check owns it?), and the fix policy for each (auto-fix vs flag for manual review) with the prudent reasoning a grader expects. Propose the exact seeded corruptions (which incident, what raw SQL simulates it, and the realistic cause story: manual SQL hotfix, legacy import, double-click without lock) so the README can list exactly what the diagnostic will find. Decide whether duplicates should be voided (columns voided_at/voided_reason) or deleted after a JSON backup, or something else — pick the most defensible for an audit table.
E. README structure (Spanish) that satisfies section 10 and makes the grader's life easy: give the section outline in order.
F. /ai-process content outline that convinces a grader the candidate DIRECTED and REVIEWED the AI (without inventing anything — it must only report real facts gathered during the build).
G. Top 5 things that would most impress you, and top 5 things that would most likely make you reject.

Return a structured report with clear keep/cut decisions and the concrete SQL. Be concise but complete.
````
