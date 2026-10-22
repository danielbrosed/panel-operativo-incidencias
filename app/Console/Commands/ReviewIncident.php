<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Incidents\ReviewFlaggedIncident;
use App\Data\Actor;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use App\Rules\CleanText;
use App\Support\CurrentActor;
use App\Support\UntrustedText;
use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ValidatedInput;
use Illuminate\Validation\Rule;

/** Exit codes: 0 reviewed; 1 nothing to review (no active flag); 2 invalid input. */
final class ReviewIncident extends Command
{
    protected $signature = 'incidents:review
        {incident : Número de la incidencia marcada para revisión manual}
        {--actor= : Quién la ha revisado: uno de los operadores de config/incidents.php}
        {--reason= : Qué se comprobó y con quién; queda en el historial}';

    protected $description = 'Cierra un caso marcado para revisión manual: registra el paso que faltaba si es uno permitido y da sus marcas por revisadas';

    public function handle(ReviewFlaggedIncident $review): int
    {
        $validator = $this->validator();

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error(UntrustedText::forTerminal($error));
            }

            return self::INVALID;
        }

        $input = new ValidatedInput($validator->validated());
        $incident = Incident::query()->findOrFail($input->integer('incident'));
        $actor = new Actor($input->string('actor')->toString());
        $outcome = $review->handle($incident, $actor, $input->string('reason')->toString());

        if (! $outcome->reviewedAnything()) {
            $this->warn(sprintf(
                'Nada que revisar: la incidencia #%d no tiene ninguna marca de revisión manual activa. No se ha modificado nada.',
                $incident->id,
            ));

            return self::FAILURE;
        }

        $step = $outcome->reconciledStep;

        $this->line(sprintf('#%d %s', $incident->id, UntrustedText::forTerminal($incident->title)));
        $this->line($step === null
            ? "Sin log de estado: {$outcome->whyNoStep}."
            : sprintf(
                'Se añade el log #%d (status_reconciled «%s» → «%s»), firmado por %s y con su motivo.',
                $step->id,
                IncidentStatus::lowerLabelOf($step->old_value),
                IncidentStatus::lowerLabelOf($step->new_value),
                $actor->name,
            ));
        $this->line('Marcas revisadas: '.collect($outcome->voidedFlags)->map(fn (IncidentLog $flag): string => sprintf(
            '#%d (%s)',
            $flag->id,
            UntrustedText::forTerminal(is_string($flag->metadata['check'] ?? null) ? $flag->metadata['check'] : 'sin comprobación'),
        ))->implode(', ').'.');
        $this->info('Revisión registrada.');

        return self::SUCCESS;
    }

    private function validator(): ValidatorContract
    {
        $operators = CurrentActor::operatorsFromConfig();
        $positiveInteger = 'El número de incidencia tiene que ser un entero positivo, por ejemplo: incidents:review 11.';
        $actor = $this->option('actor');
        $reason = $this->option('reason');

        // Console input does not go through the TrimStrings middleware.
        $input = [
            'incident' => $this->argument('incident'),
            'actor' => is_string($actor) ? trim($actor) : $actor,
            'reason' => is_string($reason) ? trim($reason) : $reason,
        ];

        return Validator::make($input, [
            'incident' => ['bail', 'required', 'integer', 'min:1', 'exists:incidents,id'],
            'actor' => ['bail', 'required', 'string', Rule::in($operators)],
            'reason' => [
                'bail',
                'required',
                'string',
                new CleanText,
                'max:'.ReviewFlaggedIncident::maxReasonLength(is_string($input['actor']) ? $input['actor'] : ''),
            ],
        ], [
            'incident.required' => $positiveInteger,
            'incident.integer' => $positiveInteger,
            'incident.min' => $positiveInteger,
            'incident.exists' => 'No existe la incidencia #:input.',
            'actor.required' => 'Falta --actor: quién ha hecho la revisión, uno de: '.implode(', ', $operators).'.',
            'actor.in' => '«:input» no es un operador del panel. --actor tiene que ser uno de: :values.',
            'reason.required' => 'Falta --reason: qué se comprobó y con quién. Queda en el historial con el nombre de quien revisa.',
            'reason.max' => 'El motivo es demasiado largo: como máximo :max caracteres.',
        ]);
    }
}
