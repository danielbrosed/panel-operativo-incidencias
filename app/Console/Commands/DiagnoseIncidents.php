<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\PrintsUntrustedText;
use App\Diagnostics\FixOutcome;
use App\Diagnostics\IncidentDiagnostics;
use App\Diagnostics\PlannedAction;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;

/**
 * Exit code 0 when there is nothing left for --fix to do (findings already flagged or reviewed are listed, but are
 * not pending); 1 when there is, when something could not be applied, when the diagnostic run again after --fix
 * still finds work, or when the fix was not confirmed.
 */
final class DiagnoseIncidents extends Command
{
    use PrintsUntrustedText;

    protected $signature = 'incidents:diagnose
        {--fix : Aplica las correcciones previstas, después de pedir confirmación}
        {--force : Con --fix, no pide confirmación (para la CI)}';

    protected $description = 'Busca incidencias cuyo estado no cuadra con su trazabilidad; sin --fix solo simula';

    /** Fixed, so every table has the same shape and the report fits in about 120 columns. */
    private const COLUMN_WIDTHS = [26, 42, 42];

    public function handle(IncidentDiagnostics $diagnostics): int
    {
        $plan = $diagnostics->plan();
        $this->report($diagnostics, $plan);

        $pending = array_values(array_filter($plan, fn (PlannedAction $action): bool => $action->isActionable()));

        if ($pending === []) {
            if ($plan !== []) {
                $this->info(self::nothingToFix($plan));
            }

            return self::SUCCESS;
        }

        if (! $this->option('fix')) {
            $this->line('Simulación: no se ha modificado nada. Ejecuta con --fix para aplicar las correcciones.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->input->isInteractive()) {
                $this->error('--fix pide confirmación: ejecútalo en una terminal o añade --force. No se ha modificado nada.');

                return self::FAILURE;
            }

            if (! $this->confirm(sprintf('¿Aplicar las %d acciones previstas?', count($pending)))) {
                $this->warn('Cancelado: no se ha modificado nada.');

                return self::FAILURE;
            }
        }

        return $this->apply($diagnostics, $pending);
    }

    /** @param  list<PlannedAction>  $plan */
    private function report(IncidentDiagnostics $diagnostics, array $plan): void
    {
        foreach ($diagnostics->checks() as $number => $check) {
            $this->newLine();
            $this->line(sprintf('<options=bold>%d. %s</> (%s)', $number + 1, $check->title(), $check->key()));

            if ($this->output->isVerbose()) {
                $this->line(OutputFormatter::escape(wordwrap('Por qué: '.$check->rationale(), 110)));
                $this->newLine();
                $this->line('SQL:');
                $this->line(OutputFormatter::escape($check->sql()));
                $this->newLine();
            }

            $actions = array_values(array_filter($plan, fn (PlannedAction $action): bool => $action->check === $check));

            if ($actions === []) {
                $this->line('Sin hallazgos.');

                continue;
            }

            $table = (new Table($this->output))
                ->setHeaders(['Incidencia', 'Detalle', 'Acción prevista'])
                ->setColumnWidths(self::COLUMN_WIDTHS)
                ->setRows(array_map(fn (PlannedAction $action): array => array_map(self::untrusted(...), [
                    "#{$action->finding->incidentId} {$action->finding->incidentTitle}",
                    $action->finding->detail,
                    $action->describe(),
                ]), $actions));

            foreach (self::COLUMN_WIDTHS as $column => $width) {
                $table->setColumnMaxWidth($column, $width);
            }

            $table->render();
        }

        $this->newLine();

        if ($plan === []) {
            $this->info('Sin inconsistencias: el estado de cada incidencia coincide con su trazabilidad.');

            return;
        }

        $automatic = count(array_filter($plan, fn (PlannedAction $a): bool => $a->isActionable() && $a->finding->fix->isAutomatic()));
        $manual = count(array_filter($plan, fn (PlannedAction $a): bool => $a->isActionable() && ! $a->finding->fix->isAutomatic()));
        $inReview = count(array_filter($plan, fn (PlannedAction $a): bool => $a->isInReview()));
        $reviewed = count(array_filter($plan, fn (PlannedAction $a): bool => $a->isReviewed()));

        $this->line(sprintf(
            'Resumen: %d automáticas, %d a revisión manual, %d ya en revisión manual%s.',
            $automatic,
            $manual,
            $inReview,
            $reviewed > 0 ? ", {$reviewed} revisadas" : '',
        ));
        $this->line(wordwrap('Regla: '.IncidentDiagnostics::RULE, 110));
        $this->newLine();
    }

    /** @param  list<PlannedAction>  $plan  every finding, none of them actionable */
    private static function nothingToFix(array $plan): string
    {
        $reviewed = count(array_filter($plan, fn (PlannedAction $a): bool => $a->isReviewed()));

        return match ($reviewed) {
            0 => 'Nada que corregir: lo que queda ya está marcado para revisión manual.',
            count($plan) => 'Nada que corregir: lo que queda ya está revisado.',
            default => 'Nada que corregir: lo que queda ya está marcado para revisión manual o revisado.',
        };
    }

    /** @param  list<PlannedAction>  $pending */
    private function apply(IncidentDiagnostics $diagnostics, array $pending): int
    {
        $applied = 0;

        foreach ($pending as $action) {
            $outcome = $diagnostics->apply($action);
            $applied += $outcome === FixOutcome::Applied ? 1 : 0;
            $message = $outcome === FixOutcome::Applied && ! $action->finding->fix->isAutomatic()
                ? 'marcada para revisión manual'
                : $outcome->message();

            $this->line(OutputFormatter::escape(sprintf(
                '#%d %s (%s): %s.',
                $action->finding->incidentId,
                self::untrusted($action->finding->incidentTitle),
                $action->check->key(),
                $message,
            )));
        }

        $skipped = count($pending) - $applied;
        $this->newLine();
        $this->line(sprintf('Resultado: aplicadas %d, omitidas %d.', $applied, $skipped));

        // A fix can uncover the next problem (voiding a duplicate exposes the log before it): plan again.
        $remaining = count(array_filter($diagnostics->plan(), fn (PlannedAction $action): bool => $action->isActionable()));

        if ($remaining > 0) {
            $this->warn(sprintf(
                'El diagnóstico, repetido después de aplicarlas, aún encuentra %d %s: vuelve a ejecutarlo.',
                $remaining,
                $remaining === 1 ? 'acción pendiente' : 'acciones pendientes',
            ));

            return self::FAILURE;
        }

        if ($skipped > 0) {
            $this->warn('Vuelve a ejecutar el diagnóstico para ver cómo han quedado.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
