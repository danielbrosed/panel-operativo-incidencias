<?php

declare(strict_types=1);

namespace Tests\Feature\Incidents;

use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ActorTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_knows_who_is_acting(): void
    {
        $this->get('/incidents')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('actor.current', 'Marta Ruiz')
            ->where('actor.options', ['Marta Ruiz', 'Jordi Vidal', 'Lucía Romero', 'Andrés Molina']));
    }

    public function test_switching_actor_changes_who_signs_the_next_actions(): void
    {
        $this->put('/actor', ['name' => 'Jordi Vidal'])->assertSessionHasNoErrors();

        $this->post('/incidents', [
            'title' => 'Rótulo de fachada apagado',
            'description' => 'No se enciende al anochecer desde ayer.',
            'priority' => 'low',
            'requester_name' => 'Rocío Navas',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Jordi Vidal', Incident::query()->sole()->logs()->sole()->user_name);
        $this->get('/incidents')->assertInertia(fn (AssertableInertia $page) => $page->where('actor.current', 'Jordi Vidal'));
    }

    public function test_only_configured_operators_can_be_chosen(): void
    {
        $this->put('/actor', ['name' => 'Alguien de fuera'])
            ->assertSessionHasErrors(['name' => 'El valor de operador no es válido.']);
    }
}
