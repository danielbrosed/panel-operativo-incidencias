<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Incidents\CreateIncident;
use App\Data\Actor;
use App\Data\NewIncidentData;
use App\Enums\IncidentPriority;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/** What an attacker would try first. The headers themselves are covered in SecurityHeadersTest. */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_markup_cannot_break_out_of_the_initial_page_data(): void
    {
        $incident = app(CreateIncident::class)->handle(new NewIncidentData(
            title: '</script><script>alert(1)</script>',
            description: '</script><img src=x onerror=alert(1)> descripción',
            priority: IncidentPriority::High,
            requesterName: '<b>Solicitante</b>',
        ), new Actor('Marta Ruiz'));

        $html = (string) $this->get("/incidents/{$incident->id}")->assertOk()->getContent();

        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        // Inertia encodes the page with JSON_HEX_TAG, so "<" and ">" travel as Unicode escapes, never as raw tags.
        $this->assertStringContainsString(trim((string) json_encode('</script>', JSON_HEX_TAG), '"'), $html);
        $this->assertSame(1, substr_count($html, '<script data-page'));
    }

    public function test_the_csp_never_allows_inline_or_eval_and_the_nonce_changes_every_request(): void
    {
        $first = (string) $this->get('/incidents')->headers->get('Content-Security-Policy');
        $second = (string) $this->get('/incidents')->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('unsafe-inline', $first);
        $this->assertStringNotContainsString('unsafe-eval', $first);
        $this->assertNotSame($first, $second);
    }

    public function test_an_unknown_csp_mode_enforces_the_policy_instead_of_disabling_it(): void
    {
        config(['security.csp' => 'Enforce']);

        $this->get('/incidents')->assertHeader('Content-Security-Policy');
    }

    public function test_a_forwarded_https_header_from_an_untrusted_proxy_does_not_enable_hsts(): void
    {
        $this->get('/incidents', ['X-Forwarded-Proto' => 'https'])->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/incidents')->assertHeader('Strict-Transport-Security');
    }

    public function test_writes_are_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->put('/actor', ['name' => 'Jordi Vidal'])->assertRedirect();
        }

        $this->put('/actor', ['name' => 'Jordi Vidal'])->assertTooManyRequests();
        // Inertia gets a redirect back with a readable message, as an error so the form keeps what was typed.
        $this->put('/actor', ['name' => 'Jordi Vidal'], ['X-Inertia' => 'true'])
            ->assertStatus(303)
            ->assertSessionHasErrors(['request' => 'Demasiadas acciones seguidas. Espera un momento y vuelve a intentarlo.']);
    }

    public function test_control_and_bidi_characters_are_rejected_in_text_fields(): void
    {
        $payload = [
            'description' => "Primera línea.\nSegunda línea, con\ttabulador.",
            'priority' => 'low',
            'requester_name' => 'Rocío Navas',
        ];

        $this->post('/incidents', [...$payload, 'title' => "Rótulo \e[2J apagado"])
            ->assertSessionHasErrors(['title' => 'El campo título contiene caracteres de control no permitidos.']);
        $this->post('/incidents', [...$payload, 'title' => "Rótulo apagado \u{202E}odagapa"])
            ->assertSessionHasErrors('title');

        // Line breaks and tabs are fine where the field is multi-line.
        $this->post('/incidents', [...$payload, 'title' => 'Rótulo de fachada apagado'])->assertSessionHasNoErrors();
        $this->assertSame(1, Incident::query()->count());
    }

    public function test_production_errors_do_not_leak_details(): void
    {
        config(['app.debug' => false]);
        Route::get('/__boom', fn () => throw new RuntimeException('secret-detail-xyz'));

        $html = (string) $this->get('/__boom')->assertStatus(500)->getContent();
        $json = (string) $this->getJson('/__boom')->assertStatus(500)->getContent();

        $this->assertStringNotContainsString('secret-detail-xyz', $html.$json);
        $this->assertStringNotContainsString('vendor', $html.$json);
    }
}
