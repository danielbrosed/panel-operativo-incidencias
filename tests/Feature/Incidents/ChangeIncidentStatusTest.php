<?php

declare(strict_types=1);

namespace Tests\Feature\Incidents;

use App\Actions\Incidents\ChangeIncidentStatus;
use App\Actions\Incidents\CreateIncident;
use App\Data\Actor;
use App\Data\NewIncidentData;
use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ChangeIncidentStatusTest extends TestCase
{
    use RefreshDatabase;

    private function openIncident(): Incident
    {
        return app(CreateIncident::class)->handle(new NewIncidentData(
            title: 'Puerta automática no abre',
            description: 'No detecta a los clientes en la entrada principal.',
            priority: IncidentPriority::High,
            requesterName: 'Óscar Prieto',
        ), new Actor('Marta Ruiz'));
    }

    private function moveTo(Incident $incident, IncidentStatus ...$path): Incident
    {
        foreach ($path as $to) {
            $incident = app(ChangeIncidentStatus::class)->handle($incident, $to, $incident->status, new Actor('Jordi Vidal'), 'Motivo de prueba');
        }

        return $incident;
    }

    public function test_an_allowed_transition_updates_the_status_and_logs_it(): void
    {
        $incident = $this->openIncident();
        $this->freezeSecond();

        $this->from("/incidents/{$incident->id}")
            ->patch("/incidents/{$incident->id}/status", ['status' => 'in_review', 'expected_status' => 'open'])
            ->assertRedirect("/incidents/{$incident->id}")
            ->assertSessionHasNoErrors();

        $this->assertSame(IncidentStatus::InReview, $incident->fresh()?->status);

        $log = $incident->logs()->latest('id')->firstOrFail();
        $this->assertSame(IncidentLogAction::StatusChanged, $log->action);
        $this->assertSame('open', $log->old_value);
        $this->assertSame('in_review', $log->new_value);
        $this->assertSame('Marta Ruiz', $log->user_name);
        $this->assertTrue($log->created_at->equalTo(now()));
    }

    public function test_a_forbidden_transition_is_rejected_without_writing_anything(): void
    {
        $incident = $this->openIncident();

        $this->patch("/incidents/{$incident->id}/status", ['status' => 'resolved', 'expected_status' => 'open'])
            ->assertSessionHasErrors(['status' => 'Una incidencia «abierta» no puede pasar a «resuelta».']);

        $this->assertSame(IncidentStatus::Open, $incident->fresh()?->status);
        $this->assertSame(1, $incident->logs()->count());
    }

    public function test_blocking_requires_a_reason_that_ends_up_in_the_trail(): void
    {
        $incident = $this->moveTo($this->openIncident(), IncidentStatus::InReview);

        $this->patch("/incidents/{$incident->id}/status", ['status' => 'blocked', 'expected_status' => 'in_review', 'reason' => '  '])
            ->assertSessionHasErrors(['reason' => 'Indica el motivo: queda en el historial de la incidencia.']);
        $this->assertSame(IncidentStatus::InReview, $incident->fresh()?->status);

        $this->patch("/incidents/{$incident->id}/status", [
            'status' => 'blocked',
            'expected_status' => 'in_review',
            'reason' => 'Esperando la pieza del fabricante.',
        ])->assertSessionHasNoErrors();

        $log = $incident->logs()->latest('id')->firstOrFail();
        $this->assertSame('blocked', $log->new_value);
        $this->assertSame(['reason' => 'Esperando la pieza del fabricante.'], $log->metadata);
    }

    public function test_reopening_a_resolved_incident_leaves_an_explicit_reopen_entry(): void
    {
        $incident = $this->moveTo($this->openIncident(), IncidentStatus::InReview, IncidentStatus::Resolved);

        $this->patch("/incidents/{$incident->id}/status", [
            'status' => 'open',
            'expected_status' => 'resolved',
            'reason' => 'La tienda avisa de que vuelve a fallar.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(IncidentStatus::Open, $incident->fresh()?->status);
        $log = $incident->logs()->latest('id')->firstOrFail();
        $this->assertSame(IncidentLogAction::Reopened, $log->action);
        $this->assertSame('resolved', $log->old_value);
        $this->assertSame('open', $log->new_value);
        $this->assertSame('La tienda avisa de que vuelve a fallar.', $log->metadata['reason'] ?? null);
    }

    public function test_acting_on_a_stale_status_is_rejected_instead_of_overwriting_someone_else(): void
    {
        $incident = $this->moveTo($this->openIncident(), IncidentStatus::InReview);
        // Someone else blocks it while this person, still seeing it "in review", goes to resolve it.
        $this->moveTo($incident, IncidentStatus::Blocked);

        $this->patch("/incidents/{$incident->id}/status", ['status' => 'resolved', 'expected_status' => 'in_review'])
            ->assertSessionHasErrors('status');

        $this->assertStringContainsString('«bloqueada» mientras la mirabas', (string) session('errors')?->first('status'));
        $this->assertSame(IncidentStatus::Blocked, $incident->fresh()?->status);
        $this->assertSame(3, $incident->logs()->count(), 'nothing written on top of the other person\'s change');
    }

    public function test_json_clients_get_409_for_a_stale_status(): void
    {
        $incident = $this->moveTo($this->openIncident(), IncidentStatus::InReview, IncidentStatus::Blocked);

        $this->patchJson("/incidents/{$incident->id}/status", ['status' => 'resolved', 'expected_status' => 'in_review'])
            ->assertStatus(409)
            ->assertJsonValidationErrors('status');
    }

    public function test_status_and_log_are_committed_together_or_not_at_all(): void
    {
        $incident = $this->openIncident();
        IncidentLog::creating(fn () => throw new RuntimeException('simulated failure while writing the log'));

        try {
            app(ChangeIncidentStatus::class)->handle($incident, IncidentStatus::InReview, IncidentStatus::Open, new Actor('Jordi Vidal'));
            $this->fail('The action should have thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('simulated failure while writing the log', $e->getMessage());
        }

        $this->assertSame(IncidentStatus::Open, $incident->fresh()?->status, 'the status update was rolled back');
        $this->assertSame(1, $incident->logs()->count());
    }

    public function test_the_row_is_locked_while_the_transition_is_applied(): void
    {
        $incident = $this->openIncident();
        DB::enableQueryLog();

        app(ChangeIncidentStatus::class)->handle($incident, IncidentStatus::InReview, IncidentStatus::Open, new Actor('Jordi Vidal'));

        $locking = collect(DB::getQueryLog())->pluck('query')->first(
            fn (string $sql) => Str::contains($sql, 'from `incidents`') && Str::contains(Str::lower($sql), 'for update'),
        );
        $this->assertNotNull($locking, 'expected a SELECT … FOR UPDATE on incidents');
    }

    public function test_unknown_target_values_are_rejected(): void
    {
        $incident = $this->openIncident();

        $this->patch("/incidents/{$incident->id}/status", ['status' => 'archived', 'expected_status' => 'open'])
            ->assertSessionHasErrors(['status' => 'El valor de estado no es válido.']);
    }

    public function test_crafted_array_input_is_a_validation_error_not_a_server_error(): void
    {
        $incident = $this->openIncident();

        $this->patch("/incidents/{$incident->id}/status", ['status' => ['x'], 'expected_status' => ['open']])
            ->assertSessionHasErrors(['status', 'expected_status']);

        $this->patchJson("/incidents/{$incident->id}/status", ['status' => ['a' => 1], 'expected_status' => 'open'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_a_double_submit_says_the_incident_is_already_there(): void
    {
        $incident = $this->openIncident();
        $payload = ['status' => 'in_review', 'expected_status' => 'open'];

        $this->patch("/incidents/{$incident->id}/status", $payload)->assertSessionHasNoErrors();
        $this->patch("/incidents/{$incident->id}/status", $payload)
            ->assertSessionHasErrors(['status' => 'La incidencia ya está «en revisión».']);

        $this->assertSame(2, $incident->logs()->count());
    }

    public function test_a_reason_with_invalid_utf8_is_rejected_cleanly(): void
    {
        $incident = $this->moveTo($this->openIncident(), IncidentStatus::InReview);

        $this->patch("/incidents/{$incident->id}/status", [
            'status' => 'blocked',
            'expected_status' => 'in_review',
            'reason' => "Pieza \xFF\xFE pendiente",
        ])->assertSessionHasErrors(['reason' => 'El campo motivo contiene caracteres no válidos.']);

        $this->assertSame(IncidentStatus::InReview, $incident->fresh()?->status);
    }
}
