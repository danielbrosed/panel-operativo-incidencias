<?php

declare(strict_types=1);

namespace Tests\Feature\Incidents;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateIncidentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'title' => 'Persiana metálica no baja',
            'description' => 'La persiana de la entrada se queda atascada a media altura.',
            'priority' => 'high',
            'requester_name' => 'Nerea Gil',
            'assigned_to' => 'Jordi Vidal',
            'store' => 'Tienda 0218 · Valencia',
            ...$overrides,
        ];
    }

    public function test_it_opens_an_incident_with_its_first_trail_entry(): void
    {
        $this->freezeSecond();

        $this->post('/incidents', $this->validPayload())
            ->assertRedirect('/incidents')
            ->assertSessionHasNoErrors();

        $incident = Incident::query()->sole();
        $this->assertSame(IncidentStatus::Open, $incident->status);
        $this->assertSame(IncidentPriority::High, $incident->priority);
        $this->assertTrue($incident->due_at->equalTo(now()->addHours(4)), 'red code: 4 hour SLA');

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::Created, $log->action);
        $this->assertNull($log->old_value);
        $this->assertSame('open', $log->new_value);
        $this->assertSame('Marta Ruiz', $log->user_name, 'first configured operator is the default actor');
        $this->assertTrue($log->created_at->equalTo(now()));
    }

    public function test_the_status_cannot_be_chosen_by_the_client(): void
    {
        $this->post('/incidents', $this->validPayload(['status' => 'resolved']))->assertSessionHasNoErrors();

        $this->assertSame(IncidentStatus::Open, Incident::query()->sole()->status);
    }

    public function test_validation_errors_are_reported_per_field_in_spanish(): void
    {
        $this->post('/incidents', ['title' => 'abc', 'priority' => 'urgent', 'assigned_to' => 'Alguien de fuera'])
            ->assertSessionHasErrors([
                'title' => 'El campo título debe tener al menos 5 caracteres.',
                'description' => 'El campo descripción es obligatorio.',
                'priority' => 'El valor de prioridad no es válido.',
                'requester_name' => 'El campo solicitante es obligatorio.',
                'assigned_to' => 'El valor de responsable no es válido.',
            ]);

        $this->assertDatabaseCount('incidents', 0);
        $this->assertDatabaseCount('incident_logs', 0);
    }

    public function test_text_with_invalid_utf8_is_a_validation_error_not_a_server_error(): void
    {
        $this->post('/incidents', $this->validPayload(['title' => "Persiana \xFF\xFE rota"]))
            ->assertSessionHasErrors(['title' => 'El campo título contiene caracteres no válidos.']);

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_optional_fields_can_be_left_empty(): void
    {
        $this->post('/incidents', $this->validPayload(['assigned_to' => '', 'store' => '']))->assertSessionHasNoErrors();

        $incident = Incident::query()->sole();
        $this->assertNull($incident->assigned_to);
        $this->assertNull($incident->store);
    }
}
