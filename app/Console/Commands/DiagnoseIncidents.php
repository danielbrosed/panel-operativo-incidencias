<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Diagnostics\FixOutcome;
use App\Diagnostics\IncidentDiagnostics;
use App\Diagnostics\PlannedAction;
use App\Diagnostics\PlanSummary;
use App\Support\UntrustedText;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;

/**
 * Exit code 0 when there is nothing left for --fix to do (flagged or reviewed findings are listed, not pending);
 * 1 when there is, when a fix was skipped or uncovered more work, or when the fix was not confirmed.
 */
final class DiagnoseIncidents extends Command
{
    protected $signature = 'incidents:diagnose
        {--fix : Aplica las correcciones previstas, después de pedir confirmación}
        {--force : Con --fix, no pide confirmación (para la CI)}';

    protected $description = 'Busca incidencias cuyo estado no cuadra con su trazabilidad; sin --fix solo simula';

    /** Fixed, so every table has the same shape and the report fits in about 120 columns. */
    private const COLUMN_WIDTHS = [26, 42, 42];

    public function handle(IncidentDiagnostics $diagnostics): int
    {
        $plan = $diagnostics->plan();
        $summary = PlanSummary::of($plan);
        $this->report($diagnostics, $plan, $summary);

        if ($summary->pending() === 0) {
            if ($summary->total() > 0) {
                $this->info(self::nothingToFix($summary));
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

            if (! $this->confirm(sprintf('¿Aplicar las %d acciones previstas?', $summary->pending()))) {
                $this->warn('Cancelado: no se ha modificado nada.');

                return self::FAILURE;
            }
        }

        return $this->apply($diagnostics, array_values(array_filter(
            $plan,
            fn (PlannedAction $action): bool => $action->isActionable(),
        )));
    }

    /** @param  list<PlannedAction>  $plan */
    private function report(IncidentDiagnostics $diagnostics, array $plan, PlanSummary $summary): void
    {
        $byCheck = collect($plan)->groupBy(fn (PlannedAction $action): string => $action->check->key());

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

            $actions = $byCheck->get($check->key());

            if ($actions === null) {
                $this->line('Sin hallazgos.');

                continue;
            }

            $table = (new Table($this->output))
                ->setHeaders(['Incidencia', 'Detalle', 'Acción prevista'])
                ->setColumnWidths(self::COLUMN_WIDTHS)
                ->setRows($actions->map(fn (PlannedAction $action): array => array_map(UntrustedText::forTerminal(...), [
                    "#{$action->finding->incidentId} {$action->finding->incidentTitle}",
                    $action->finding->detail,
                    $action->describe(),
                ]))->all());

            foreach (self::COLUMN_WIDTHS as $column => $width) {
                $table->setColumnMaxWidth($column, $width);
            }

            $table->render();
        }

        $this->newLine();

        if ($summary->total() === 0) {
            $this->info('Sin inconsistencias: el estado de cada incidencia coincide con su trazabilidad.');

            return;
        }

        $this->line(sprintf(
            'Resumen: %d automáticas, %d a revisión manual, %d ya en revisión manual%s.',
            $summary->automatic,
            $summary->manual,
            $summary->flagPending,
            $summary->flagReviewed > 0 ? ", {$summary->flagReviewed} revisadas" : '',
        ));
        $this->line(wordwrap('Regla: '.IncidentDiagnostics::RULE, 110));
        $this->newLine();
    }

    /** For a plan where every finding is already flagged or reviewed. */
    private static function nothingToFix(PlanSummary $summary): string
    {
        return match ($summary->flagReviewed) {
            0 => 'Nada que corregir: lo que queda ya está marcado para revisión manual.',
            $summary->total() => 'Nada que corregir: lo que queda ya está revisado.',
            default => 'Nada que corregir: lo que queda ya está marcado para revisión manual o revisado.',
        };
    }

    /** @param  list<PlannedAction>  $pending */
    private function apply(IncidentDiagnostics $diagnostics, array $pending): int
    {
        $corrected = $flagged = $skipped = 0;

        foreach ($pending as $action) {
            $fix = $action->finding->fix;
            $outcome = $diagnostics->apply($action);

            if ($outcome !== FixOutcome::Applied) {
                $skipped++;
            } elseif ($fix->isAutomatic()) {
                $corrected++;
            } else {
                $flagged++;
            }

            $this->line(OutputFormatter::escape(sprintf(
                '#%d %s (%s): %s.',
                $action->finding->incidentId,
                UntrustedText::forTerminal($action->finding->incidentTitle),
                $action->check->key(),
                $outcome->message($fix),
            )));
        }

        $this->newLine();
        $this->line(sprintf('Resultado: corregidas %d, marcadas para revisión %d, omitidas %d.', $corrected, $flagged, $skipped));

        // A fix can uncover the next problem (voiding a duplicate exposes the log before it): plan again.
        $remaining = PlanSummary::of($diagnostics->plan())->pending();

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
