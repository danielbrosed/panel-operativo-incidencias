<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Incident;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\DevTools\DevTools;
use RuntimeException;
use Tests\Concerns\CreatesIncidents;
use Tests\TestCase;

/** What an attacker would try first. The headers themselves are covered in SecurityHeadersTest. */
class SecurityTest extends TestCase
{
    use CreatesIncidents;
    use RefreshDatabase;

    private const NEW_INCIDENT = [
        'title' => 'Rótulo de fachada apagado',
        'description' => "Primera línea.\nSegunda línea, con\ttabulador.",
        'priority' => 'low',
        'requester_name' => 'Rocío Navas',
    ];

    public function test_stored_markup_cannot_break_out_of_the_initial_page_data(): void
    {
        $incident = $this->openIncident(
            title: '</script><script>alert(1)</script>',
            description: '</script><img src=x onerror=alert(1)> descripción',
            requesterName: '<b>Solicitante</b>',
        );

        $html = (string) $this->get("/incidents/{$incident->id}")->assertOk()->getContent();

        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        // Inertia encodes the page with JSON_HEX_TAG, so "<" and ">" travel as Unicode escapes, never as raw tags.
        $this->assertStringContainsString(trim((string) json_encode('</script>', JSON_HEX_TAG), '"'), $html);
        $this->assertSame(1, substr_count($html, '<script data-page'));
    }

    public function test_writes_are_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->put('/actor', ['name' => 'Jordi Vidal'])->assertRedirect();
        }

        $this->put('/actor', ['name' => 'Jordi Vidal'])->assertTooManyRequests();
        // Inertia gets a redirect back with a readable message, as an error so the form keeps what was typed. Back
        // means this host: only the path and the query of the Referer are used.
        $this->put('/actor', ['name' => 'Jordi Vidal'], ['X-Inertia' => 'true', 'Referer' => 'https://elsewhere.example/incidents?status=open'])
            ->assertStatus(303)
            ->assertRedirect('/incidents?status=open')
            ->assertSessionHasErrors(['request' => 'Demasiadas acciones seguidas. Espera un momento y vuelve a intentarlo.']);
    }

    public function test_control_and_bidi_characters_are_rejected_in_text_fields(): void
    {
        $this->post('/incidents', [...self::NEW_INCIDENT, 'title' => "Rótulo \e[2J apagado"])
            ->assertSessionHasErrors(['title' => 'El campo título contiene caracteres de control no permitidos.']);
        $this->post('/incidents', [...self::NEW_INCIDENT, 'title' => "Rótulo apagado \u{202E}odagapa"])
            ->assertSessionHasErrors('title');
        $this->post('/incidents', [...self::NEW_INCIDENT, 'title' => "Rótulo\u{200B} apagado\u{FEFF}"])
            ->assertSessionHasErrors('title');

        // Line breaks and tabs are fine where the field is multi-line.
        $this->post('/incidents', self::NEW_INCIDENT)->assertSessionHasNoErrors();
        $this->assertSame(1, Incident::query()->count());
    }

    public function test_invisible_characters_are_rejected_but_emoji_sequences_pass(): void
    {
        foreach ([
            "Fuga en almacén \u{E0072}\u{E006D}", // Unicode tags, which can hide an instruction
            "Fuga\u{2060} en almacén", // word joiner
            "Fuga\u{00AD} en almacén", // soft hyphen
            "Fuga\u{3164} en almacén", // Hangul filler
            "Fuga en almacén\u{E0101}", // supplementary variation selector
        ] as $title) {
            $this->post('/incidents', [...self::NEW_INCIDENT, 'title' => $title])->assertSessionHasErrors('title');
        }

        // ZWJ (emoji sequences), VS16 and ZWNJ are part of real text.
        $this->post('/incidents', [...self::NEW_INCIDENT, 'title' => "Fuga en almacén \u{1F468}\u{200D}\u{1F527} \u{2764}\u{FE0F} \u{200C}"])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Incident::query()->count());
    }

    public function test_in_local_only_loopback_and_the_app_url_host_are_answered(): void
    {
        // Without authentication, a page that rebinds its own domain to 127.0.0.1 (DNS rebinding) must get nothing.
        $this->app['env'] = 'local';
        config(['app.url' => 'http://panel.test']);
        (new AppServiceProvider($this->app))->boot();

        try {
            $this->get('http://rebound.example/incidents')->assertStatus(400);
            $this->post('http://rebound.example/incidents', self::NEW_INCIDENT)->assertStatus(400);
            $this->assertSame(0, Incident::query()->count());

            foreach (['127.0.0.1', 'localhost', '[::1]', 'panel.test'] as $host) {
                $this->get("http://{$host}/incidents")->assertOk();
            }
        } finally {
            Request::setTrustedHosts([]);
        }
    }

    public function test_inertia_devtools_stay_off_in_local_unless_enabled_explicitly(): void
    {
        $default = $this->devtoolsConfig(config_path('inertia.php'), null);
        $package = $this->devtoolsConfig(base_path('vendor/inertiajs/inertia-laravel/config/inertia.php'), null);

        // Laravel merges config top-level keys only, so the block is the package's whole, but off by default.
        $this->assertSame([...$package, 'enabled' => false], $default);
        $this->assertTrue($this->devtoolsConfig(config_path('inertia.php'), 'true')['enabled']);

        // In local, the package would otherwise record and serve every request.
        config(['inertia.devtools' => $default]);
        $this->app['env'] = 'local';
        $this->assertFalse(DevTools::enabled());
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

    /**
     * The `devtools` block of a config file, read with INERTIA_DEVTOOLS_ENABLED set to $value (null: not set).
     *
     * @return array<mixed>
     */
    private function devtoolsConfig(string $file, ?string $value): array
    {
        $name = 'INERTIA_DEVTOOLS_ENABLED';
        $saved = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];
        unset($_SERVER[$name], $_ENV[$name]);
        putenv($name);

        if ($value !== null) {
            $_SERVER[$name] = $value;
        }

        try {
            $config = require $file;
        } finally {
            unset($_SERVER[$name]);
            [$server, $env, $putenv] = $saved;

            if ($server !== null) {
                $_SERVER[$name] = $server;
            }
            if ($env !== null) {
                $_ENV[$name] = $env;
            }
            if ($putenv !== false) {
                putenv("{$name}={$putenv}");
            }
        }

        $this->assertIsArray($config);
        $this->assertIsArray($config['devtools']);

        return $config['devtools'];
    }
}
