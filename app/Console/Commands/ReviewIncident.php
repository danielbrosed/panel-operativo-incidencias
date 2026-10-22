<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Commands\Concerns\PrintsUntrustedText;
use App\Diagnostics\StatusTrail;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use App\Rules\CleanText;
use App\Support\CurrentActor;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Closes a case that `incidents:diagnose` flagged for manual review, once a person has found out what happened.
 *
 * When the latest status log leaves the incident in S, the incident is in C and S → C is one allowed transition,
 * the person's conclusion is recorded as a status_reconciled S → C signed by them, dated now and with their reason:
 * the missing step the diagnostic may not invent, now with a real author and reason. Otherwise (several steps
 * missing, a chain broken in the middle of the trail) no status log is written. Either way the incident's active
 * flags are voided as reviewed; the voided flag is the record of the review and the diagnostic never raises it again.
 * The incident's status is never changed.
 *
 * Exit codes: 0 reviewed; 1 nothing to review (no active flag); 2 invalid input.
 */
final class ReviewIncident extends Command
{
    use PrintsUntrustedText;

    protected $signature = 'incidents:review
        {incident : Número de la incidencia marcada para revisión manual}
        {--actor= : Quién la ha revisado: uno de los operadores de config/incidents.php}
        {--reason= : Qué se comprobó y con quién; queda en el historial}';

    protected $description = 'Cierra un caso marcado para revisión manual: registra el paso que faltaba si es uno permitido y da sus marcas por revisadas';

    /** incident_logs.voided_reason is VARCHAR(255) and stores "Revisada por {actor}: {reason}". */
    private const VOIDED_REASON_LENGTH = 255;

    public function handle(): int
    {
        $input = $this->validInput();

        if ($input === null) {
            return self::INVALID;
        }

        [$incidentId, $actor, $reason] = $input;

        /** @var array{Incident, IncidentLog|null, string|null, Collection<int, IncidentLog>}|null $review */
        $review = DB::transaction(fn (): ?array => $this->review($incidentId, $actor, $reason));

        if ($review === null) {
            $this->warn(sprintf(
                'Nada que revisar: la incidencia #%d no tiene ninguna marca de revisión manual activa. No se ha modificado nada.',
                $incidentId,
            ));

            return self::FAILURE;
        }

        [$incident, $step, $whyNoStep, $flags] = $review;

        $this->line(sprintf('#%d %s', $incident->id, self::untrusted($incident->title)));
        $this->line($step === null
            ? "Sin log de estado: {$whyNoStep}."
            : sprintf(
                'Se añade el log #%d (status_reconciled «%s» → «%s»), firmado por %s y con su motivo.',
                $step->id,
                self::label($step->old_value),
                self::label($step->new_value),
                $actor,
            ));
        $this->line('Marcas revisadas: '.$flags->map(fn (IncidentLog $flag): string => sprintf(
            '#%d (%s)',
            $flag->id,
            self::untrusted(is_string($flag->metadata['check'] ?? null) ? $flag->metadata['check'] : 'sin comprobación'),
        ))->implode(', ').'.');
        $this->info('Revisión registrada.');

        return self::SUCCESS;
    }

    /**
     * Runs inside the transaction, with the incident row locked: null when there is no active flag to review.
     *
     * @return array{Incident, IncidentLog|null, string|null, Collection<int, IncidentLog>}|null
     */
    private function review(int $incidentId, string $actor, string $reason): ?array
    {
        $incident = Incident::query()->whereKey($incidentId)->lockForUpdate()->firstOrFail();
        $flags = $incident->logs()->where('action', IncidentLogAction::FlaggedForReview)->active()->orderBy('id')->get();

        if ($flags->isEmpty()) {
            return null;
        }

        $latest = StatusTrail::latest($incident->id);
        $logged = $latest === null ? null : IncidentStatus::tryFrom((string) $latest->new_value);
        $whyNoStep = self::whyNoStep($latest, $logged, $incident->status);
        $step = null;

        if ($whyNoStep === null && $logged !== null) {
            $step = $incident->logs()->create([
                'action' => IncidentLogAction::StatusReconciled,
                'old_value' => $logged->value,
                'new_value' => $incident->status->value,
                'user_name' => $actor,
                'metadata' => ['reason' => $reason, 'reviewed' => true, 'inferred' => false],
            ]);
        }

        $flags->each(fn (IncidentLog $flag) => $flag->void("Revisada por {$actor}: {$reason}"));

        return [$incident, $step, $whyNoStep, $flags];
    }

    /** Null when the gap is exactly one allowed transition from the latest status log to the current status. */
    private static function whyNoStep(?IncidentLog $latest, ?IncidentStatus $logged, IncidentStatus $current): ?string
    {
        return match (true) {
            $latest === null, $logged === null => 'no tiene ningún log de estado válido del que partir',
            $logged === $current => sprintf('su último log de estado ya la deja «%s», como está', self::label($current->value)),
            ! $logged->canTransitionTo($current) => sprintf(
                'de «%s» a «%s» no hay un único paso permitido',
                self::label($logged->value),
                self::label($current->value),
            ),
            StatusTrail::isFutureDated($latest->id) => sprintf(
                'su último log de estado (#%d) tiene fecha futura y uno añadido ahora quedaría antes que él',
                $latest->id,
            ),
            default => null,
        };
    }

    /** @return array{int, string, string}|null null after printing every problem with the input */
    private function validInput(): ?array
    {
        $incident = $this->argument('incident');
        $actor = $this->option('actor');
        $reason = $this->option('reason');
        $operators = CurrentActor::operatorsFromConfig();
        $errors = [];

        $incidentId = is_string($incident) && preg_match('/^[1-9][0-9]{0,17}$/', $incident) === 1 ? (int) $incident : null;

        if ($incidentId === null) {
            $errors[] = 'El número de incidencia tiene que ser un entero positivo, por ejemplo: incidents:review 11.';
        } elseif (! Incident::query()->whereKey($incidentId)->exists()) {
            $errors[] = "No existe la incidencia #{$incidentId}.";
        }

        $actor = is_string($actor) ? trim($actor) : '';

        if ($actor === '') {
            $errors[] = 'Falta --actor: quién ha hecho la revisión, uno de: '.implode(', ', $operators).'.';
        } elseif (! in_array($actor, $operators, true)) {
            $errors[] = sprintf(
                '«%s» no es un operador del panel. --actor tiene que ser uno de: %s.',
                self::untrusted($actor),
                implode(', ', $operators),
            );
        }

        $reason = is_string($reason) ? trim($reason) : '';
        $maxLength = self::VOIDED_REASON_LENGTH - mb_strlen("Revisada por {$actor}: ");

        if ($reason === '') {
            $errors[] = 'Falta --reason: qué se comprobó y con quién. Queda en el historial con el nombre de quien revisa.';
        } elseif (! mb_check_encoding($reason, 'UTF-8') || preg_match('/[\p{Cc}'.CleanText::FORMAT_CHARACTERS.']/u', $reason) === 1) {
            $errors[] = 'El motivo no puede llevar caracteres de control, saltos de línea ni marcas de dirección del texto.';
        } elseif (mb_strlen($reason) > $maxLength) {
            $errors[] = sprintf('El motivo es demasiado largo: como máximo %d caracteres.', $maxLength);
        }

        foreach ($errors as $error) {
            $this->error($error);
        }

        return $errors === [] && $incidentId !== null ? [$incidentId, $actor, $reason] : null;
    }

    private static function label(?string $status): string
    {
        return mb_strtolower(IncidentStatus::tryFrom((string) $status)?->label() ?? self::untrusted((string) $status));
    }
}
