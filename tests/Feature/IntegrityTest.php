<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Incidents\CreateIncident;
use App\Data\Actor;
use App\Data\NewIncidentData;
use App\Enums\IncidentPriority;
use App\Exceptions\Incidents\ImmutableIncidentLog;
use App\Models\Incident;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
}
