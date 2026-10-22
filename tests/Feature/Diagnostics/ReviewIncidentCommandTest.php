<?php

declare(strict_types=1);

namespace Tests\Feature\Diagnostics;

use App\Actions\Incidents\ChangeIncidentStatus;
use App\Data\Actor;
use App\Diagnostics\FixOutcome;
use App\Diagnostics\IncidentDiagnostics;
use App\Diagnostics\PlannedAction;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReviewIncidentCommandTest extends TestCase
{
    use RefreshDatabase;
    use WritesTrailsBySql;

    // The two seeded cases --fix flags (see InconsistenciesSeeder).
    private const B = 'Persiana metálica de acceso no sube';

    private const D = 'Cuadro eléctrico salta de forma intermitente';

    private const B_REASON = 'La tienda confirmó por teléfono la reapertura del 19 de octubre.';

    private const D_REASON = 'Importación revisada con el sistema anterior: el paso a revisión se dio el mismo día.';

    private const FIX = ['--fix' => true, '--force' => true];

    public function test_reviewing_the_seeded_flags_closes_them_and_the_trail_stays_clean_afterwards(): void
    {
        $this->seed();
        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        [$b, $d] = [$this->incident(self::B), $this->incident(self::D)];
        $startedAt = now()->startOfSecond();

        // B: the reopen that was missing, now written by the person who confirmed it, with their reason.
        $this->assertSame(0, $this->review($b, 'Marta Ruiz', self::B_REASON));
        $step = $b->logs()->where('action', IncidentLogAction::StatusReconciled)->sole();
        $this->assertSame([IncidentStatus::Resolved->value, IncidentStatus::Open->value], [$step->old_value, $step->new_value]);
        $this->assertSame('Marta Ruiz', $step->user_name);
        $metadata = (array) $step->metadata;
        ksort($metadata); // MySQL's JSON type reorders the keys
        $this->assertSame(['inferred' => false, 'reason' => self::B_REASON, 'reviewed' => true], $metadata);
        $this->assertTrue($step->created_at->greaterThanOrEqualTo($startedAt), 'never backdated');
        $bFlag = $b->logs()->where('action', IncidentLogAction::FlaggedForReview)->sole();
        $this->assertSame('Revisada por Marta Ruiz: '.self::B_REASON, $bFlag->voided_reason);

        // D: the gap is in the middle of the trail and no single step fills it: no status log, the flag is reviewed.
        $dLogs = $d->logs()->count();
        $this->assertSame(0, $this->review($d, 'Andrés Molina', self::D_REASON));
        $this->assertStringContainsString('Sin log de estado', Artisan::output());
        $this->assertSame($dLogs, $d->logs()->count());
        $this->assertFalse($d->logs()->where('action', IncidentLogAction::StatusReconciled)->exists());
        $this->assertSame(
            'Revisada por Andrés Molina: '.self::D_REASON,
            $d->logs()->where('action', IncidentLogAction::FlaggedForReview)->sole()->voided_reason,
        );

        // Neither shows the «Revisión manual» badge; nothing is pending: B is gone and D is listed as reviewed.
        $this->assertFalse($this->hasReviewBadge($b));
        $this->assertFalse($this->hasReviewBadge($d));
        $this->assertSame(0, Artisan::call('incidents:diagnose'));
        $output = Artisan::output();
        $this->assertDoesNotMatchRegularExpression('/^\| #'.$b->id.' /m', $output);
        $this->assertMatchesRegularExpression('/^\| #'.$d->id.' .*Revisada por Andrés Molina/mu', $output);
        $this->assertStringContainsString('Resumen: 0 automáticas, 0 a revisión manual, 0 ya en revisión manual, 1 revisadas.', $output);
        $this->assertStringContainsString('Nada que corregir: lo que queda ya está revisado.', $output);

        // Both carry on through the panel; the diagnostic stays at 0 and --fix writes nothing.
        $change = app(ChangeIncidentStatus::class);
        $change->handle($b, IncidentStatus::InReview, IncidentStatus::Open, new Actor('Marta Ruiz'));
        $change->handle($d, IncidentStatus::InReview, IncidentStatus::Blocked, new Actor('Andrés Molina'));
        $before = $this->snapshot();

        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertSame($before, $this->snapshot());
    }

    public function test_no_status_log_when_no_single_allowed_step_explains_the_gap(): void
    {
        // Resolved by hand straight from "blocked": blocked → resolved is not a transition.
        $id = $this->writtenBySql('Escaparate sin iluminación', IncidentStatus::Resolved, [
            [IncidentLogAction::Created, null, IncidentStatus::Open->value, 0],
            [IncidentLogAction::StatusChanged, IncidentStatus::Open->value, IncidentStatus::InReview->value, 600],
            [IncidentLogAction::StatusChanged, IncidentStatus::InReview->value, IncidentStatus::Blocked->value, 1200],
        ]);
        Artisan::call('incidents:diagnose', self::FIX);
        $incident = Incident::query()->findOrFail($id);
        $logs = $incident->logs()->count();

        $this->assertSame(0, $this->review($incident, 'Jordi Vidal', 'El técnico confirma que la dejó resuelta.'));

        $this->assertStringContainsString('no hay un único paso permitido', Artisan::output());
        $this->assertSame($logs, $incident->logs()->count());
        $this->assertTrue($incident->logs()->where('action', IncidentLogAction::FlaggedForReview)->sole()->isVoided());
        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertSame($logs, $incident->logs()->count(), 'the reviewed flag is not raised again');
    }

    public function test_nothing_to_review_without_an_active_flag(): void
    {
        $this->seed();
        Artisan::call('incidents:diagnose', self::FIX);
        $b = $this->incident(self::B);
        $this->review($b, 'Marta Ruiz', self::B_REASON);
        $before = $this->snapshot();

        foreach ([$b, $this->incident('Puerta del almacén no cierra bien')] as $incident) {
            $this->assertSame(1, $this->review($incident, 'Marta Ruiz', 'Otra vez.'));
            $this->assertStringContainsString('Nada que revisar', Artisan::output());
        }

        $this->assertSame($before, $this->snapshot());
    }

    public function test_invalid_input_is_refused_with_a_clear_message_and_writes_nothing(): void
    {
        $this->seed();
        Artisan::call('incidents:diagnose', self::FIX);
        $id = (string) $this->incident(self::B)->id;
        $before = $this->snapshot();
        $valid = ['incident' => $id, '--actor' => 'Marta Ruiz', '--reason' => self::B_REASON];

        foreach ([
            'El número de incidencia tiene que ser un entero positivo' => ['incident' => 'once'],
            'No existe la incidencia #999999.' => ['incident' => '999999'],
            'Falta --actor' => ['--actor' => null],
            '«marta ruiz» no es un operador del panel' => ['--actor' => 'marta ruiz'],
            'Falta --reason' => ['--reason' => '   '],
            'no puede llevar caracteres de control' => ['--reason' => "Confirmado\e[2J por teléfono"],
            // voided_reason is VARCHAR(255) and holds "Revisada por Marta Ruiz: " (25 characters) plus the reason.
            'El motivo es demasiado largo: como máximo 230 caracteres.' => ['--reason' => str_repeat('a', 231)],
        ] as $message => $override) {
            $this->assertSame(2, Artisan::call('incidents:review', array_merge($valid, $override)), $message);
            $this->assertStringContainsString($message, Artisan::output());
        }

        $this->assertSame($before, $this->snapshot());
    }

    public function test_a_fix_planned_before_the_review_is_skipped_as_already_reviewed(): void
    {
        $this->seed();
        $diagnostics = app(IncidentDiagnostics::class);
        $planned = collect($diagnostics->plan())->sole(fn (PlannedAction $action): bool => $action->finding->incidentTitle === self::D);

        // Between that plan and its --fix, another run flags D and a person reviews it.
        Artisan::call('incidents:diagnose', self::FIX);
        $this->review($this->incident(self::D), 'Andrés Molina', self::D_REASON);

        $this->assertSame(FixOutcome::AlreadyReviewed, $diagnostics->apply($planned));
        $this->assertSame(1, IncidentLog::query()->where('incident_id', $planned->finding->incidentId)->where('action', IncidentLogAction::FlaggedForReview)->count());
    }

    private function review(Incident $incident, string $actor, string $reason): int
    {
        return Artisan::call('incidents:review', ['incident' => (string) $incident->id, '--actor' => $actor, '--reason' => $reason]);
    }

    private function incident(string $title): Incident
    {
        return Incident::query()->where('title', $title)->sole();
    }

    private function hasReviewBadge(Incident $incident): bool
    {
        return (bool) Incident::query()->withReviewFlag()->findOrFail($incident->id)->getAttribute('flagged_for_review');
    }
}
