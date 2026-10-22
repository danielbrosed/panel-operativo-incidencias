<?php

declare(strict_types=1);

namespace Tests\Feature\Diagnostics;

use App\Diagnostics\Fixes\FlagForReview;
use App\Diagnostics\Fixes\ReconcileStatus;
use App\Diagnostics\Fixes\VoidDuplicateLog;
use App\Diagnostics\FixOutcome;
use App\Diagnostics\IncidentDiagnostics;
use App\Diagnostics\PlannedAction;
use App\Diagnostics\PlanSummary;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Database\Seeders\DemoIncidentsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;
use Tests\Concerns\CreatesIncidents;
use Tests\TestCase;

class DiagnoseIncidentsCommandTest extends TestCase
{
    use CreatesIncidents;
    use RefreshDatabase;
    use SeededInconsistencies;
    use WritesTrailsBySql;

    public function test_the_demo_data_alone_has_no_inconsistencies(): void
    {
        $this->seed(DemoIncidentsSeeder::class);

        $this->assertSame(0, Artisan::call('incidents:diagnose'));
        $this->assertStringContainsString('Sin inconsistencias', Artisan::output());
    }

    public function test_each_seeded_case_is_found_by_its_check_with_the_expected_fix(): void
    {
        $this->seed();

        $found = [];
        foreach (app(IncidentDiagnostics::class)->plan() as $action) {
            $found[$action->check->key()][] = [$action->finding->incidentTitle, $action->finding->fix::class];
        }

        $this->assertSame([
            'resolved_without_resolution_log' => [[self::RESOLVED_BY_SQL, ReconcileStatus::class]],
            'status_differs_from_last_log' => [[self::REOPENED_BY_SQL, FlagForReview::class]],
            'duplicate_or_incoherent_log' => [[self::DOUBLE_CLICK, VoidDuplicateLog::class], [self::IMPORTED_WITH_GAP, FlagForReview::class]],
        ], $found);
    }

    public function test_the_dry_run_reports_the_seeded_cases_and_changes_nothing(): void
    {
        $this->seed();
        $before = $this->snapshot();

        $this->assertSame(1, Artisan::call('incidents:diagnose'));
        $output = Artisan::output();

        foreach (app(IncidentDiagnostics::class)->checks() as $check) {
            $this->assertStringContainsString($check->title(), $output);
        }
        foreach ([self::RESOLVED_BY_SQL, self::REOPENED_BY_SQL, self::DOUBLE_CLICK, self::IMPORTED_WITH_GAP] as $title) {
            $this->assertListsIncident($output, $this->incidentTitled($title));
        }
        $this->assertStringContainsString('Resumen: 2 automáticas, 2 a revisión manual, 0 ya en revisión manual.', $output);
        $this->assertStringContainsString(IncidentDiagnostics::RULE, str_replace("\n", ' ', $output));
        $this->assertStringContainsString('Simulación: no se ha modificado nada.', $output);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_fix_reconciles_the_sql_resolution_voids_the_double_click_and_flags_the_rest(): void
    {
        $this->seed();
        $startedAt = now()->startOfSecond();
        $incidentsBefore = $this->snapshot()['incidents'];
        $logsBefore = IncidentLog::query()->count();
        $legitimateReopen = $this->incidentTitled(self::LEGITIMATE_REOPEN);
        $legitimateTrail = $legitimateReopen->logs()->orderBy('id')->get()->toArray();
        $resolvedBySql = $this->incidentTitled(self::RESOLVED_BY_SQL);
        $doubleClick = $this->incidentTitled(self::DOUBLE_CLICK);
        $doubleClickLogs = $doubleClick->logs()->count();

        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertStringContainsString('Resultado: corregidas 2, marcadas para revisión 2, omitidas 0.', Artisan::output());

        // The missing in_review → resolved step, dated now and marked as inferred.
        $reconciled = $resolvedBySql->logs()->where('action', IncidentLogAction::StatusReconciled)->sole();
        $this->assertSame([IncidentStatus::InReview->value, IncidentStatus::Resolved->value], [$reconciled->old_value, $reconciled->new_value]);
        $this->assertSame(config()->string('incidents.diagnostics.actor'), $reconciled->user_name);
        $this->assertTrue($reconciled->metadata['inferred'] ?? null);
        $this->assertSame('resolved_without_resolution_log', $reconciled->metadata['check'] ?? null);
        $this->assertTrue($reconciled->created_at->greaterThanOrEqualTo($startedAt), 'never backdated');

        // The later copy of the double click is voided with a reason that points at the original, and kept.
        $this->assertSame($doubleClickLogs, $doubleClick->logs()->count());
        $voided = $doubleClick->logs()->whereNotNull('voided_at')->sole();
        $original = $doubleClick->logs()->active()->where('new_value', IncidentStatus::Blocked->value)->sole();
        $this->assertGreaterThan($original->id, $voided->id);
        $this->assertStringContainsString("Duplicado del log #{$original->id}", (string) $voided->voided_reason);

        // One manual-review flag each for the other two, pointing at the log that shows the problem.
        foreach ([self::REOPENED_BY_SQL, self::IMPORTED_WITH_GAP] as $title) {
            $flag = $this->incidentTitled($title)->logs()->reviewFlags()->sole();
            $this->assertSame(config()->string('incidents.diagnostics.actor'), $flag->user_name);
            $this->assertIsInt($flag->metadata['evidence_log_id'] ?? null);
            $this->assertNotEmpty($flag->metadata['detail'] ?? null);
        }

        // Exactly three new rows, no incident touched (status included) and the legitimate reopen left alone.
        $this->assertSame($logsBefore + 3, IncidentLog::query()->count());
        $this->assertSame($incidentsBefore, $this->snapshot()['incidents']);
        $this->assertSame($legitimateTrail, $legitimateReopen->logs()->orderBy('id')->get()->toArray());
    }

    public function test_a_second_fix_writes_nothing_and_flagged_cases_stay_listed(): void
    {
        $this->seed();
        Artisan::call('incidents:diagnose', self::FIX);
        $afterFirstFix = $this->snapshot();

        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertSame($afterFirstFix, $this->snapshot());

        $plan = app(IncidentDiagnostics::class)->plan();
        $this->assertEquals(new PlanSummary(automatic: 0, manual: 0, flagPending: 2, flagReviewed: 0), PlanSummary::of($plan));
        $this->assertEqualsCanonicalizing(
            [self::REOPENED_BY_SQL, self::IMPORTED_WITH_GAP],
            array_map(fn (PlannedAction $action): string => $action->finding->incidentTitle, $plan),
        );

        $this->assertSame(0, Artisan::call('incidents:diagnose'));
        $this->assertStringContainsString('Ya marcada para revisión manual', Artisan::output());
    }

    public function test_fix_without_force_applies_nothing_when_it_cannot_ask(): void
    {
        $this->seed();
        $before = $this->snapshot();

        $this->assertSame(1, Artisan::call('incidents:diagnose', ['--fix' => true, '--no-interaction' => true]));
        $this->assertStringContainsString('No se ha modificado nada', Artisan::output());
        $this->assertSame($before, $this->snapshot());
    }

    public function test_fix_asks_for_confirmation_before_applying(): void
    {
        $this->seed();
        $before = $this->snapshot();

        $this->fixAnswering('no')->assertExitCode(1)->run();
        $this->assertSame($before, $this->snapshot());

        $this->fixAnswering('yes')->assertExitCode(0)->run();
        $this->assertSame(1, IncidentLog::query()->where('action', IncidentLogAction::StatusReconciled)->count());
        $this->assertSame(2, IncidentLog::query()->reviewFlags()->count());
    }

    public function test_a_resolved_incident_whose_last_log_is_blocked_is_flagged_not_reconciled(): void
    {
        // blocked → resolved is not an allowed step (it goes back to review first): no single step explains the gap.
        $id = $this->writtenBySql('Escaparate sin iluminación', IncidentStatus::Resolved, [
            [IncidentLogAction::Created, null, IncidentStatus::Open->value, 0],
            [IncidentLogAction::StatusChanged, IncidentStatus::Open->value, IncidentStatus::InReview->value, 600],
            [IncidentLogAction::StatusChanged, IncidentStatus::InReview->value, IncidentStatus::Blocked->value, 1200],
        ]);

        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));

        $incident = Incident::query()->findOrFail($id);
        $this->assertSame(IncidentStatus::Resolved, $incident->status);
        $this->assertFalse($incident->logs()->where('action', IncidentLogAction::StatusReconciled)->exists());
        $flag = $incident->logs()->reviewFlags()->sole();
        $this->assertSame('resolved_without_resolution_log', $flag->metadata['check'] ?? null);
        $this->assertStringContainsString('no hay un paso permitido', (string) ($flag->metadata['detail'] ?? ''));
    }

    public function test_a_planned_fix_is_skipped_if_the_incident_changed_after_the_plan(): void
    {
        $this->seed();
        $diagnostics = app(IncidentDiagnostics::class);
        $action = collect($diagnostics->plan())->sole(fn (PlannedAction $action): bool => $action->finding->incidentTitle === self::RESOLVED_BY_SQL);

        // Meanwhile someone puts it back in review by hand: its trail matches again and there is nothing to reconcile.
        DB::table('incidents')->where('id', $action->finding->incidentId)->update(['status' => IncidentStatus::InReview->value]);

        $this->assertSame(FixOutcome::NoLongerTrue, $diagnostics->apply($action));
        $this->assertFalse(IncidentLog::query()->where('action', IncidentLogAction::StatusReconciled)->exists());
    }

    public function test_a_logged_status_the_enum_does_not_know_is_flagged_instead_of_crashing(): void
    {
        $id = $this->writtenBySql('Fuente de agua sin presión', IncidentStatus::Open, [
            [IncidentLogAction::Created, null, IncidentStatus::Open->value, 0],
            [IncidentLogAction::StatusChanged, IncidentStatus::Open->value, 'archived', 600],
        ]);

        $this->assertSame(1, Artisan::call('incidents:diagnose'));
        $this->assertStringContainsString('la deja «archived»', Artisan::output());

        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $flags = IncidentLog::query()->where('incident_id', $id)->reviewFlags()->get();
        $this->assertEqualsCanonicalizing(
            ['status_differs_from_last_log', 'duplicate_or_incoherent_log'],
            $flags->map(fn (IncidentLog $flag): mixed => $flag->metadata['check'] ?? null)->all(),
        );
        $detail = $flags->first(fn (IncidentLog $flag): bool => ($flag->metadata['check'] ?? null) === 'status_differs_from_last_log')
            ?->metadata['detail'] ?? null;
        $this->assertIsString($detail);
        $this->assertStringContainsString('no tiene un estado válido', $detail);
    }

    public function test_a_reconcile_after_a_future_dated_log_is_flagged_so_fix_stays_idempotent(): void
    {
        // A log written in the future (a session in another time zone, a typo in an import): a reconcile dated now
        // would sort before it, so every --fix would add another one.
        $id = $this->writtenBySql('Alarma de la trastienda sin batería', IncidentStatus::Resolved, [
            [IncidentLogAction::Created, null, IncidentStatus::Open->value, 0],
            [IncidentLogAction::StatusChanged, IncidentStatus::Open->value, IncidentStatus::InReview->value, now()->addDay()->startOfSecond()],
        ]);

        $fix = collect(app(IncidentDiagnostics::class)->plan())->sole()->finding->fix;
        $this->assertInstanceOf(FlagForReview::class, $fix);
        $this->assertStringContainsString('fecha futura', $fix->why);

        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertStringContainsString('(resolved_without_resolution_log): marcada para revisión manual.', Artisan::output());
        $afterFirstFix = $this->snapshot();
        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));

        $this->assertSame($afterFirstFix, $this->snapshot());
        $this->assertFalse(IncidentLog::query()->where('incident_id', $id)->where('action', IncidentLogAction::StatusReconciled)->exists());
        $this->assertSame(1, IncidentLog::query()->where('incident_id', $id)->reviewFlags()->count());
    }

    public function test_no_reconcile_on_a_trail_with_a_duplicate_and_fix_reports_the_work_it_uncovers(): void
    {
        // Resolved by hand, and its last log is a double-click copy: the reconcile would build on the copy.
        $id = $this->writtenBySql('Toldo de la entrada descolgado', IncidentStatus::Resolved, [
            [IncidentLogAction::Created, null, IncidentStatus::Open->value, 0],
            [IncidentLogAction::StatusChanged, IncidentStatus::Open->value, IncidentStatus::InReview->value, 600],
            [IncidentLogAction::StatusChanged, IncidentStatus::Open->value, IncidentStatus::InReview->value, 601],
        ]);

        $fixes = collect(app(IncidentDiagnostics::class)->plan())->mapWithKeys(fn (PlannedAction $action): array => [
            $action->check->key() => $action->finding->fix,
        ]);
        $this->assertInstanceOf(FlagForReview::class, $fixes['resolved_without_resolution_log']);
        $this->assertStringContainsString('duplicado o incoherente', $fixes['resolved_without_resolution_log']->why);
        $this->assertInstanceOf(VoidDuplicateLog::class, $fixes['duplicate_or_incoherent_log']);

        // Voiding the copy uncovers a reconcile that is now safe: --fix applies everything it planned, then says so.
        $this->assertSame(1, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertStringContainsString('aún encuentra 1 acción pendiente', Artisan::output());

        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $reconciled = IncidentLog::query()->where('incident_id', $id)->where('action', IncidentLogAction::StatusReconciled)->sole();
        $original = IncidentLog::query()->where('incident_id', $id)->active()->where('new_value', IncidentStatus::InReview->value)->sole();
        $this->assertSame($original->id, $reconciled->metadata['evidence_log_id'] ?? null);

        $settled = $this->snapshot();
        $this->assertSame(0, Artisan::call('incidents:diagnose', self::FIX));
        $this->assertSame($settled, $this->snapshot());
    }

    public function test_no_reconcile_on_a_trail_that_does_not_start_with_created(): void
    {
        // blocked → in_review alone would be reconciled, but nothing says how the incident got to "blocked".
        $this->writtenBySql('Cámara frigorífica sin revisar', IncidentStatus::InReview, [
            [IncidentLogAction::StatusChanged, IncidentStatus::InReview->value, IncidentStatus::Blocked->value, 0],
        ]);

        $fix = collect(app(IncidentDiagnostics::class)->plan())
            ->sole(fn (PlannedAction $action): bool => $action->check->key() === 'status_differs_from_last_log')
            ->finding->fix;

        $this->assertInstanceOf(FlagForReview::class, $fix);
        $this->assertStringContainsString('no empieza por el log de apertura', $fix->why);
    }

    public function test_text_from_the_database_reaches_the_terminal_without_control_characters_or_tags(): void
    {
        $this->writtenBySql("\e[2J<error>x</error>\u{202E}", IncidentStatus::Resolved, []);

        Artisan::call('incidents:diagnose');
        $report = Artisan::output();
        Artisan::call('incidents:diagnose', self::FIX);
        $fix = Artisan::output();

        foreach ([$report, $fix] as $output) {
            $this->assertStringNotContainsString("\e", $output);
            $this->assertStringNotContainsString("\u{202E}", $output);
            $this->assertStringNotContainsString('<error>', $output);
            $this->assertStringContainsString('[2J‹error›x‹/error›', $output);
        }
    }

    public function test_verbose_prints_the_exact_sql_each_check_runs(): void
    {
        Artisan::call('incidents:diagnose', ['-v' => true]);
        $output = Artisan::output();

        foreach (app(IncidentDiagnostics::class)->checks() as $check) {
            $this->assertStringContainsString($check->sql(), $output);
        }
    }

    /** The report starts each row with "#id title"; the title may wrap, so only its first words are checked. */
    private function assertListsIncident(string $output, Incident $incident): void
    {
        $this->assertStringContainsString("#{$incident->id} ".mb_substr($incident->title, 0, 12), $output);
    }

    private function fixAnswering(string $answer): PendingCommand
    {
        $command = $this->artisan('incidents:diagnose', ['--fix' => true]);
        $this->assertInstanceOf(PendingCommand::class, $command);

        return $command->expectsConfirmation('¿Aplicar las 4 acciones previstas?', $answer);
    }
}
