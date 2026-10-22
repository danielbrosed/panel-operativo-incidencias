<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\CurrentActor;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentActor::class, fn (Application $app): CurrentActor => new CurrentActor(
            $app->make('session.store'),
            CurrentActor::operatorsFromConfig(),
        ));
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        // trustHosts() is off in local, where there is no authentication either: answer only to loopback and to
        // APP_URL's host, so a page that rebinds its own domain to 127.0.0.1 cannot read or drive the panel.
        if ($this->app->isLocal()) {
            Request::setTrustedHosts(self::localHosts());
        }

        // Outside production: lazy loading (N+1), silently discarded attributes and missing attributes throw.
        Model::shouldBeStrict(! $this->app->isProduction());

        DB::prohibitDestructiveCommands($this->app->isProduction());

        JsonResource::withoutWrapping();

        RateLimiter::for('writes', fn (Request $request): Limit => Limit::perMinute(60)->by((string) $request->ip()));
    }

    /** @return list<string> host patterns, as Symfony's Request::setTrustedHosts() takes them */
    private static function localHosts(): array
    {
        $appHost = parse_url(config()->string('app.url'), PHP_URL_HOST);
        $hosts = ['^(localhost|127\.0\.0\.1|\[::1\])$'];

        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = '^'.preg_quote($appHost).'$';
        }

        return $hosts;
    }
}
