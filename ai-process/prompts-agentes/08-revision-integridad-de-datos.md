# Revisión adversaria: integridad de datos, concurrencia y diagnóstico

- **Fase:** Revisión final
- **Tipo de agente:** general-purpose

Encargo literal (las rutas de la máquina local están sustituidas por marcadores entre `< >`):

````text
You are an adversarial reviewer focused on data integrity, transactions, concurrency and the data-repair command of a Laravel 13 + MySQL 8.4 technical test (an incident panel with an append-only audit trail). READ-ONLY on the repository <repo>: do not edit/create/delete files there; no state-changing git. Scratch files (probe tests, scripts) only in <scratchpad>\review-integrity\.

Environment: Windows; Git Bash `source <tools>/env.sh` first; MySQL 127.0.0.1:3306 root/no password, client `mysql --defaults-file=<tools>/mysql-8.4/my.ini <db>`. Use ONLY databases `incidents_diag` and `incidents_testing_diag` (they exist; e.g. `DB_DATABASE=incidents_diag php artisan migrate:fresh --seed`, `DB_DATABASE=incidents_testing_diag php artisan test`), plus any database you create whose name starts with `incidents_review_int`. Never touch `incidents`, `incidents_live`, `incidents_testing`.

Read CLAUDE.md, .claude/skills/data-fix-protocol/SKILL.md and incident-domain/SKILL.md, then app/Enums, app/Models, app/Actions, app/Diagnostics, app/Console/Commands/DiagnoseIncidents.php, database/migrations, database/seeders (incl. data/demo-incidents.php and InconsistenciesSeeder.php), tests/. Then try to break the guarantees with evidence:
1. Status changes: can a status ever be committed without its log or vice versa? Can two concurrent requests both succeed (write a real two-connection race: one mysql session holding SELECT ... FOR UPDATE while the action runs, or two PHP processes)? Stale-status and double-submit behaviour. Rollback on log failure.
2. Append-only log: any code path (including the diagnostic and seeders) that updates or deletes logs other than void(); voiding twice; void then the diagnostic.
3. The diagnostic: run it on many hand-crafted corruptions beyond the 4 seeded (no logs at all; resolved with last log blocked; open with last log in_review; duplicate created logs; logs out of chronological order / backdated; a trail starting with status_changed; a voided log in the middle; equal created_at timestamps; a reopened log without reason; status_reconciled entries; flagged incidents later fixed by a person through normal transitions; very old duplicates outside the 2 s window). For each: what it reports, what --fix does, is the result prudent per the rule ("auto only if the status doesn't change, nothing is invented, and one legal transition without reason explains the gap; else flag"), is it idempotent (second --fix writes nothing), does dry-run write nothing, are exit codes right, any crash. Verify the re-check under lock really prevents applying a stale plan (simulate a change between dry-run planning and apply if possible).
4. SQL correctness: read the three check queries (php artisan incidents:diagnose -v) and reason about NULL handling (<=>), ordering ties, window frames, the IN list built from the enum, performance notes.
5. Seeders: deterministic ids/contents, time-travel reset, each seeded inconsistency triggers only its own check, demo data alone gives 0 findings, the legit reopen is not reported.
6. Schema: FKs, CHECKs (status, priority, incident_logs.action), defaults on created_at/updated_at, indexes vs queries (EXPLAIN the list query with filters and the diagnostic queries on the seeded data).
For each finding: severity (critical/major/minor/nit), evidence (commands/outputs), and a concrete fix. Clearly separate real defects from documented, acceptable limitations. End with a short verdict.
````
