<?php

declare(strict_types=1);

namespace Tests\Feature\Diagnostics;

use App\Actions\Incidents\ChangeIncidentStatus;
use App\Data\Actor;
use App\Diagnostics\FixOutcome;
use App\Diagnostics\IncidentDiagnostics;
use App\Diagnostics\PlannedAction;
use App\Diagnostics\PlanSummary;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesIncidents;
use Tests\TestCase;

class ReviewIncidentCommandTest extends TestCase
{
    use CreatesIncidents;
    use RefreshDatabase;
    use SeededInconsistencies;
    use WritesTrailsBySql;

    private const REOPEN_REASON = 'La tienda confirmó por teléfono la reapertura del 19 de octubre.';

    private const IMPORT_REASON = 'Importación revisada con el sistema anterior: el paso a revisión se dio el mismo día.';

    public function test_reviewing_the_seeded_flags_closes_them_and_the_trail_stays_clean_afterwards(): void
    {
        $this->seed();
        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $reopened = $this->incidentTitled(self::REOPENED_BY_SQL);
        $imported = $this->incidentTitled(self::IMPORTED_WITH_GAP);
        $startedAt = now()->startOfSecond();

        // The reopen that was missing, now written by the person who confirmed it, with their reason.
        $this->assertSame(0, $this->review($reopened, 'Marta Ruiz', self::REOPEN_REASON));
        $step = $reopened->logs()->where('action', IncidentLogAction::StatusReconciled)->sole();
        $this->assertSame([IncidentStatus::Resolved->value, IncidentStatus::Open->value], [$step->old_value, $step->new_value]);
        $this->assertSame('Marta Ruiz', $step->user_name);
        $metadata = (array) $step->metadata;
        ksort($metadata); // MySQL's JSON type reorders the keys
        $this->assertSame(['inferred' => false, 'reason' => self::REOPEN_REASON, 'reviewed' => true], $metadata);
        $this->assertTrue($step->created_at->greaterThanOrEqualTo($startedAt), 'never backdated');
        $this->assertSame('Revisada por Marta Ruiz: '.self::REOPEN_REASON, $reopened->logs()->reviewFlags()->sole()->voided_reason);

        // The import gap is in the middle of the trail and no single step fills it: no status log, the flag is reviewed.
        $importedLogs = $imported->logs()->count();
        $this->assertSame(0, $this->review($imported, 'Andrés Molina', self::IMPORT_REASON));
        $this->assertStringContainsString('Sin log de estado', Artisan::output());
        $this->assertSame($importedLogs, $imported->logs()->count());
        $this->assertFalse($imported->logs()->where('action', IncidentLogAction::StatusReconciled)->exists());
        $this->assertSame('Revisada por Andrés Molina: '.self::IMPORT_REASON, $imported->logs()->reviewFlags()->sole()->voided_reason);

        // Neither shows the «Revisión manual» badge; nothing is pending: the reopen is gone, the import is reviewed.
        $this->assertFalse($this->hasReviewBadge($reopened));
        $this->assertFalse($this->hasReviewBadge($imported));
        $plan = app(IncidentDiagnostics::class)->plan();
        $this->assertEquals(new PlanSummary(automatic: 0, manual: 0, flagPending: 0, flagReviewed: 1), PlanSummary::of($plan));
        $this->assertSame($imported->id, collect($plan)->sole()->finding->incidentId);
        $this->assertSame(0, Artisan::call('incidents:diagnose'));
        $output = Artisan::output();
        $this->assertStringContainsString('Revisada por Andrés Molina', $output);
        $this->assertStringContainsString('Nada que corregir: lo que queda ya está revisado.', $output);

        // Both carry on through the panel; the diagnostic stays at 0 and --fix writes nothing.
        $change = app(ChangeIncidentStatus::class);
        $change->handle($reopened, IncidentStatus::InReview, IncidentStatus::Open, new Actor('Marta Ruiz'));
        $change->handle($imported, IncidentStatus::InReview, IncidentStatus::Blocked, new Actor('Andrés Molina'));
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
        $this->assertTrue($incident->logs()->reviewFlags()->sole()->isVoided());
        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertSame($logs, $incident->logs()->count(), 'the reviewed flag is not raised again');
    }

    public function test_a_review_does_not_cover_a_different_mismatch_that_shows_up_later_on_the_same_log(): void
    {
        // The trail ends in review and the row says open (in_review → open is not a transition): flagged and reviewed.
        $id = $this->writtenBySql('Rótulo de la entrada sin luz', IncidentStatus::Open, [
            [IncidentLogAction::Created, null, IncidentStatus::Open->value, 0],
            [IncidentLogAction::StatusChanged, IncidentStatus::Open->value, IncidentStatus::InReview->value, 60],
        ]);
        Artisan::call('incidents:diagnose', self::FIX);
        $this->assertSame(0, $this->review(Incident::query()->findOrFail($id), 'Marta Ruiz', 'Comprobado con la tienda.'));
        $this->assertSame(0, Artisan::call('incidents:diagnose'));

        // A second, different UPDATE by hand on the same trail: in_review → blocked has no log and needs a reason.
        DB::table('incidents')->where('id', $id)->update(['status' => IncidentStatus::Blocked->value]);

        $this->assertSame(1, Artisan::call('incidents:diagnose'));
        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertTrue($this->hasReviewBadge(Incident::query()->findOrFail($id)));
    }

    public function test_nothing_to_review_without_an_active_flag(): void
    {
        $this->seed();
        Artisan::call('incidents:diagnose', self::FIX);
        $reopened = $this->incidentTitled(self::REOPENED_BY_SQL);
        $this->review($reopened, 'Marta Ruiz', self::REOPEN_REASON);
        $before = $this->snapshot();

        foreach ([$reopened, $this->incidentTitled(self::LEGITIMATE_REOPEN)] as $incident) {
            $this->assertSame(1, $this->review($incident, 'Marta Ruiz', 'Otra vez.'));
            $this->assertStringContainsString('Nada que revisar', Artisan::output());
        }

        $this->assertSame($before, $this->snapshot());
    }

    public function test_invalid_input_is_refused_with_a_clear_message_and_writes_nothing(): void
    {
        $this->seed();
        Artisan::call('incidents:diagnose', self::FIX);
        $id = (string) $this->incidentTitled(self::REOPENED_BY_SQL)->id;
        $before = $this->snapshot();
        $valid = ['incident' => $id, '--actor' => 'Marta Ruiz', '--reason' => self::REOPEN_REASON];

        foreach ([
            'El número de incidencia tiene que ser un entero positivo' => ['incident' => 'once'],
            'No existe la incidencia #999999.' => ['incident' => '999999'],
            'Falta --actor' => ['--actor' => null],
            '«marta ruiz» no es un operador del panel' => ['--actor' => 'marta ruiz'],
            'Falta --reason' => ['--reason' => '   '],
            'El campo motivo contiene caracteres de control no permitidos.' => ['--reason' => "Confirmado\e[2J por teléfono"],
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
        $planned = collect($diagnostics->plan())->sole(fn (PlannedAction $action): bool => $action->finding->incidentTitle === self::IMPORTED_WITH_GAP);

        // Between that plan and its --fix, another run flags the import and a person reviews it.
        Artisan::call('incidents:diagnose', self::FIX);
        $this->review($this->incidentTitled(self::IMPORTED_WITH_GAP), 'Andrés Molina', self::IMPORT_REASON);

        $this->assertSame(FixOutcome::AlreadyReviewed, $diagnostics->apply($planned));
        $this->assertSame(1, IncidentLog::query()->where('incident_id', $planned->finding->incidentId)->reviewFlags()->count());
    }

    private function review(Incident $incident, string $actor, string $reason): int
    {
        return Artisan::call('incidents:review', ['incident' => (string) $incident->id, '--actor' => $actor, '--reason' => $reason]);
    }

    private function hasReviewBadge(Incident $incident): bool
    {
        return (bool) Incident::query()->withReviewFlag()->findOrFail($incident->id)->getAttribute('flagged_for_review');
    }
}
