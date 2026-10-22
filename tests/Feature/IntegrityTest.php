<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Incidents\CreateIncident;
use App\Data\Actor;
use App\Data\NewIncidentData;
use App\Enums\IncidentPriority;
use App\Exceptions\Incidents\ImmutableIncidentLog;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Guarantees that hold even when someone bypasses the actions. */
class IntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function incident(): Incident
    {
        return app(CreateIncident::class)->handle(new NewIncidentData(
            title: 'Cerradura de la puerta trasera rota',
            description: 'La llave gira en vacío en la puerta de mercancías.',
            priority: IncidentPriority::High,
            requesterName: 'Marina Castro',
        ), new Actor('Marta Ruiz'));
    }

    public function test_a_log_entry_cannot_be_edited(): void
    {
        $log = $this->incident()->logs()->sole();

        $this->expectException(ImmutableIncidentLog::class);
        $log->update(['new_value' => 'resolved']);
    }

    public function test_a_log_entry_cannot_be_deleted(): void
    {
        $log = $this->incident()->logs()->sole();

        $this->expectException(ImmutableIncidentLog::class);
        $log->delete();
    }

    public function test_a_log_entry_can_be_voided_once_and_is_kept(): void
    {
        $log = $this->incident()->logs()->sole();

        $log->void('Duplicado del log #1');

        $fresh = $log->fresh();
        $this->assertNotNull($fresh?->voided_at);
        $this->assertSame('Duplicado del log #1', $fresh->voided_reason);

        $this->expectException(ImmutableIncidentLog::class);
        $fresh->void('Otro motivo');
    }

    public function test_two_stale_copies_of_a_log_cannot_both_void_it(): void
    {
        $id = $this->incident()->logs()->sole()->id;
        // Both loaded before either voids, like two processes acting at the same time.
        [$first, $second] = [IncidentLog::query()->findOrFail($id), IncidentLog::query()->findOrFail($id)];

        $first->void('Primer motivo');
        $this->assertSame('Primer motivo', $first->voided_reason, 'the instance is refreshed');

        try {
            $second->void('Segundo motivo');
            $this->fail('The second void went through.');
        } catch (ImmutableIncidentLog) {
            $this->assertSame('Primer motivo', IncidentLog::query()->findOrFail($id)->voided_reason);
        }
    }

    public function test_a_log_cannot_be_voided_without_a_reason(): void
    {
        $log = $this->incident()->logs()->sole();

        try {
            $log->void('   ');
            $this->fail('A log was voided without a reason.');
        } catch (InvalidArgumentException) {
            $this->assertFalse($log->fresh()?->isVoided());
        }
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function halfVoids(): array
    {
        return [
            'voided without a reason' => [['voided_at' => '2026-10-22 10:00:00']],
            'a reason on an active row' => [['voided_reason' => 'Motivo sin anular']],
        ];
    }

    /** @param  array<string, mixed>  $columns */
    #[DataProvider('halfVoids')]
    public function test_the_database_requires_voided_at_and_voided_reason_together(array $columns): void
    {
        $log = $this->incident()->logs()->sole();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Check constraint 'incident_logs_void_check' is violated");
        DB::table('incident_logs')->where('id', $log->id)->update($columns);
    }

    /**
     * Values utf8mb4_unicode_ci would take as equal to an allowed one (case, accents, trailing spaces). The columns
     * use a binary NO PAD collation so the CHECKs compare exactly and the enum casts never meet them.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function lookAlikeValues(): array
    {
        return [
            'status in capitals' => ['incidents', 'status', 'OPEN'],
            'status with a trailing space' => ['incidents', 'status', 'open '],
            'status with an accent' => ['incidents', 'status', 'résolved'],
            'priority in capitals' => ['incidents', 'priority', 'HIGH'],
            'action capitalised' => ['incident_logs', 'action', 'Created'],
            'action in capitals with a trailing space' => ['incident_logs', 'action', 'FLAGGED_FOR_REVIEW '],
        ];
    }

    #[DataProvider('lookAlikeValues')]
    public function test_the_database_rejects_values_that_only_look_like_an_allowed_one(string $table, string $column, string $value): void
    {
        $incident = $this->incident();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage("Check constraint '{$table}_{$column}_check' is violated");

        if ($table === 'incidents') {
            DB::table('incidents')->insert([
                'title' => 'Alta manual',
                'description' => 'Insertada por SQL.',
                'status' => 'open',
                'priority' => 'low',
                'requester_name' => 'Soporte',
                'due_at' => now()->addDay(),
                $column => $value,
            ]);
        } else {
            DB::table('incident_logs')->insert([
                'incident_id' => $incident->id,
                'action' => 'created',
                'new_value' => 'open',
                'user_name' => 'Soporte',
                $column => $value,
            ]);
        }
    }

    public function test_status_values_compare_exactly(): void
    {
        $this->incident();

        $this->assertSame(1, DB::table('incidents')->where('status', 'open')->count());
        $this->assertSame(0, DB::table('incidents')->where('status', 'OPEN')->count());
        $this->assertSame(0, DB::table('incident_logs')->where('new_value', 'open ')->count());
    }

    public function test_the_database_rejects_an_unknown_status_even_outside_the_app(): void
    {
        $incident = $this->incident();

        $this->expectException(QueryException::class);
        DB::table('incidents')->where('id', $incident->id)->update(['status' => 'archived']);
    }

    public function test_an_incident_with_a_trail_cannot_be_deleted(): void
    {
        $incident = $this->incident();

        $this->expectException(QueryException::class);
        DB::table('incidents')->where('id', $incident->id)->delete();
    }

    public function test_the_database_rejects_an_unknown_log_action(): void
    {
        $incident = $this->incident();

        $this->expectException(QueryException::class);
        DB::table('incident_logs')->insert([
            'incident_id' => $incident->id,
            'action' => 'legacy_import',
            'user_name' => 'importacion-legacy',
        ]);
    }

    public function test_rows_written_outside_the_app_without_dates_still_render(): void
    {
        $id = DB::table('incidents')->insertGetId([
            'title' => 'Alta manual sin fechas',
            'description' => 'Insertada por SQL sin created_at ni updated_at.',
            'status' => 'open',
            'priority' => 'low',
            'requester_name' => 'Soporte',
            'due_at' => now()->addDay(),
        ]);
        DB::table('incident_comments')->insert(['incident_id' => $id, 'author_name' => 'Soporte', 'body' => 'Sin fecha.']);

        $this->get('/incidents')->assertOk();
        $this->get("/incidents/{$id}")->assertOk();
    }
}
