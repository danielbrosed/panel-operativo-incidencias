<?php

declare(strict_types=1);

namespace Tests\Feature\Incidents;

use App\Actions\Incidents\ChangeIncidentStatus;
use App\Data\Actor;
use App\Enums\IncidentStatus;
use App\Support\CurrentActor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\CreatesIncidents;
use Tests\TestCase;

class CommentsAndTimelineTest extends TestCase
{
    use CreatesIncidents;
    use RefreshDatabase;

    public function test_an_operator_adds_a_comment(): void
    {
        $incident = $this->openIncident();

        $this->from("/incidents/{$incident->id}")
            ->post("/incidents/{$incident->id}/comments", ['body' => '  Solicitud enviada al centro comercial.  '])
            ->assertRedirect("/incidents/{$incident->id}")
            ->assertSessionHasNoErrors();

        $comment = $incident->comments()->sole();
        $this->assertSame('Solicitud enviada al centro comercial.', $comment->body);
        $this->assertSame(CurrentActor::operatorsFromConfig()[0], $comment->author_name, 'the default actor');
        $this->assertSame(1, $incident->logs()->count(), 'a comment does not write a trail entry');
    }

    public function test_an_empty_comment_is_rejected(): void
    {
        $incident = $this->openIncident();

        $this->post("/incidents/{$incident->id}/comments", ['body' => ''])
            ->assertSessionHasErrors(['body' => 'El campo comentario es obligatorio.']);
        $this->assertSame(0, $incident->comments()->count());
    }

    public function test_the_detail_page_shows_changes_and_comments_in_one_chronological_history(): void
    {
        $this->travelTo(now()->subHours(3));
        $incident = $this->openIncident();
        $this->travelTo(now()->addHour());
        $incident = app(ChangeIncidentStatus::class)->handle($incident, IncidentStatus::InReview, IncidentStatus::Open, new Actor('Lucía Romero'));
        $this->travelTo(now()->addHour());
        $this->post("/incidents/{$incident->id}/comments", ['body' => 'Fontanero avisado.']);
        $this->travelBack();

        $this->get("/incidents/{$incident->id}")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('incidents/show')
            ->where('incident.id', $incident->id)
            ->where('incident.description', $incident->description)
            ->where('incident.flagged_for_review', false)
            ->has('timeline', 3)
            ->where('timeline.0.type', 'log')
            ->where('timeline.0.action.value', 'created')
            ->where('timeline.1.type', 'log')
            ->where('timeline.1.old_label', 'Abierta')
            ->where('timeline.1.new_label', 'En revisión')
            ->where('timeline.1.user_name', 'Lucía Romero')
            ->where('timeline.2.type', 'comment')
            ->where('timeline.2.body', 'Fontanero avisado.'));
    }

    public function test_an_unknown_incident_is_a_404(): void
    {
        $this->get('/incidents/999')->assertNotFound();
        $this->post('/incidents/999/comments', ['body' => 'Hola'])->assertNotFound();
        $this->get('/incidents/abc')->assertNotFound();
    }
}
