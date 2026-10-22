---
name: data-fix-protocol
description: Úsala antes de escribir o cambiar cualquier corrección de datos (comando incidents:diagnose, seeders de inconsistencias, scripts de reparación). Fija cómo se detecta, cuándo se corrige solo, cuándo se marca para revisión y qué nunca se hace con la auditoría.
---

# Protocolo para corregir datos

## Regla única: cuándo se corrige automáticamente

Solo cuando se cumplen las tres:

1. la corrección **no cambia el estado** de la incidencia;
2. **no hay que inventar** fecha, autor ni motivo;
3. el hueco se explica con **una única transición permitida** por la máquina de estados que **no exige motivo**.

Todo lo demás se **marca para revisión manual** (log `flagged_for_review`) y se explica en la salida por qué.

## Nunca

- Borrar filas de `incident_logs`. Un log sobrante se **anula** (`voided_at` + `voided_reason`), no se borra.
- Atrasar fechas: un log reconstruido lleva la fecha real de la corrección y `metadata.inferred = true`.
- Cambiar `incidents.status` desde el diagnóstico: si la fila y el log no coinciden, no sabemos cuál dice la verdad.
- Corregir en masa con un `UPDATE` sin pasar por la misma lista de acciones que enseña el dry-run.

## Siempre

- **Dry-run por defecto.** `--fix` aplica exactamente la lista de acciones que el dry-run enseña.
- Cada corrección en **su propia transacción**, con la incidencia bloqueada (`lockForUpdate`) y el hallazgo
  **comprobado otra vez** dentro del bloqueo: entre el dry-run y el `--fix` alguien pudo cambiar algo.
- **Idempotente**: una segunda pasada no escribe nada; un caso ya marcado se informa, no se vuelve a marcar.
- Actor `config('incidents.diagnostics.actor')` y `metadata` con la comprobación y el log que sirve de evidencia.
- El SQL de cada comprobación es el que se documenta en el README, literal (`-v` lo imprime).
- Código de salida 1 si queda algo que `--fix` haría; 0 si no (los casos en revisión manual se listan igual).

## Tests mínimos de cualquier cambio aquí

- Con los datos limpios del seeder de demostración, el diagnóstico no encuentra nada.
- El dry-run no cambia ni una fila (comparar una foto de `incidents` e `incident_logs` antes y después).
- `--fix` hace exactamente lo previsto para cada caso sembrado.
- Una segunda pasada con `--fix` no añade filas.
