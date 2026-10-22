<?php

declare(strict_types=1);

namespace Tests\Feature\Incidents;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ListIncidentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_redirects_to_the_list(): void
    {
        $this->get('/')->assertRedirect('/incidents');
    }

    public function test_it_lists_incidents_newest_first_with_counts_and_options(): void
    {
        $older = Incident::factory()->create(['created_at' => now()->subDay()]);
        $newer = Incident::factory()->status(IncidentStatus::Blocked)->create();

        $this->get('/incidents')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('incidents/index')
            ->has('incidents.data', 2)
            ->where('incidents.data.0.id', $newer->id)
            ->where('incidents.data.1.id', $older->id)
            ->where('incidents.data.0.status', ['value' => 'blocked', 'label' => 'Bloqueada'])
            ->where('counts', ['open' => 1, 'in_review' => 0, 'blocked' => 1, 'resolved' => 0])
            ->has('options.statuses', 4)
            ->has('options.priorities', 3)
            ->where('filters', ['status' => null, 'priority' => null, 'q' => null]));
    }

    public function test_each_incident_carries_only_the_transitions_the_server_allows(): void
    {
        Incident::factory()->status(IncidentStatus::InReview)->create();

        $this->get('/incidents')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('incidents.data.0.transitions', [
                [
                    'to' => ['value' => 'blocked', 'label' => 'Bloqueada'],
                    'action_label' => 'Bloquear',
                    'requires_reason' => true,
                    'requires_confirmation' => true,
                ],
                [
                    'to' => ['value' => 'resolved', 'label' => 'Resuelta'],
                    'action_label' => 'Resolver',
                    'requires_reason' => false,
                    'requires_confirmation' => true,
                ],
            ]));
    }

    public function test_it_filters_by_status_and_priority(): void
    {
        $match = Incident::factory()->status(IncidentStatus::Open)->priority(IncidentPriority::High)->create();
        Incident::factory()->status(IncidentStatus::Open)->priority(IncidentPriority::Low)->create();
        Incident::factory()->status(IncidentStatus::Resolved)->priority(IncidentPriority::High)->create();

        $this->get('/incidents?status=open&priority=high')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('incidents.data', 1)
            ->where('incidents.data.0.id', $match->id)
            ->where('filters', ['status' => 'open', 'priority' => 'high', 'q' => null]));
    }

    public function test_counters_ignore_the_status_filter_but_respect_the_others(): void
    {
        Incident::factory()->status(IncidentStatus::Open)->priority(IncidentPriority::High)->create();
        Incident::factory()->status(IncidentStatus::Blocked)->priority(IncidentPriority::High)->create();
        Incident::factory()->status(IncidentStatus::Blocked)->priority(IncidentPriority::Low)->create();

        $this->get('/incidents?status=open&priority=high')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('incidents.data', 1)
            ->where('counts', ['open' => 1, 'in_review' => 0, 'blocked' => 1, 'resolved' => 0]));
    }

    public function test_free_text_searches_title_store_and_people(): void
    {
        $byTitle = Incident::factory()->create(['title' => 'Goteras en el almacén']);
        $byStore = Incident::factory()->create(['store' => 'Tienda Goteras 12']);
        $byRequester = Incident::factory()->create(['requester_name' => 'Ana Goteras']);
        Incident::factory()->create(['title' => 'Otra cosa', 'store' => 'Otra tienda', 'requester_name' => 'Luis']);

        $response = $this->get('/incidents?q=goteras');

        $found = array_column($response->inertiaProps('incidents.data'), 'id');
        sort($found);
        $this->assertSame([$byTitle->id, $byStore->id, $byRequester->id], $found);
    }

    public function test_like_wildcards_in_the_search_are_matched_literally(): void
    {
        $percent = Incident::factory()->create(['title' => 'Etiquetas al 100% en caja']);
        Incident::factory()->create(['title' => 'Ticket 1000 sin imprimir']);
        $underscore = Incident::factory()->create(['title' => 'Código a_b en el TPV']);
        Incident::factory()->create(['title' => 'Código axb en el TPV']);

        $this->get('/incidents?q='.urlencode('100%'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('incidents.data', 1)
            ->where('incidents.data.0.id', $percent->id));

        $this->get('/incidents?q=a_b')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('incidents.data', 1)
            ->where('incidents.data.0.id', $underscore->id));
    }

    public function test_unknown_filter_values_are_ignored_instead_of_failing(): void
    {
        Incident::factory()->count(2)->create();

        $this->get('/incidents?status=archived&priority[]=x&q[]=y')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('incidents.data', 2)
            ->where('filters', ['status' => null, 'priority' => null, 'q' => null]));
    }

    public function test_the_quick_filter_partial_reload_only_computes_what_it_asks_for(): void
    {
        Incident::factory()->status(IncidentStatus::Open)->create();
        Incident::factory()->status(IncidentStatus::Blocked)->create();

        $this->get('/incidents?status=blocked')->assertInertia(fn (AssertableInertia $page) => $page
            ->reloadOnly(['incidents', 'counts', 'filters'], fn (AssertableInertia $reload) => $reload
                ->has('incidents.data', 1)
                ->has('counts')
                ->missing('options')
                ->missing('urls')));
    }

    public function test_it_paginates_keeping_the_filters_in_the_links(): void
    {
        Incident::factory()->count(17)->status(IncidentStatus::Open)->create();

        $this->get('/incidents?status=open')->assertInertia(fn (AssertableInertia $page) => $page
            ->has('incidents.data', 15)
            ->where('incidents.meta.total', 17)
            ->where('incidents.links.next', fn (string $url) => str_contains($url, 'status=open') && str_contains($url, 'page=2')));
    }

    public function test_overdue_incidents_are_flagged(): void
    {
        Incident::factory()->status(IncidentStatus::Open)->create(['due_at' => now()->subHour()]);
        Incident::factory()->status(IncidentStatus::Resolved)->create(['due_at' => now()->subHour(), 'created_at' => now()->subDay()]);

        $this->get('/incidents')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('incidents.data.0.is_overdue', true)
            ->where('incidents.data.1.is_overdue', false));
    }
}
