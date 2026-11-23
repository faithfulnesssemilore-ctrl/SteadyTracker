<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
        $this->configureDefaults();

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        $trustedProxies = config('app.trusted_proxies');
        if (is_array($trustedProxies) && $trustedProxies !== []) {
            TrustProxies::at($trustedProxies);
            TrustProxies::withHeaders(
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO,
            );
        }

        Mail::extend('brevo', function (array $config): BrevoApiTransport {
            $transport = (new BrevoTransportFactory)->create(new Dsn(
                'brevo+api',
                'default',
                (string) ($config['api_key'] ?? ''),
            ));

            if (! $transport instanceof BrevoApiTransport) {
                throw new \LogicException('Brevo API transport was not created.');
            }

            return $transport;
        });

        RateLimiter::for('csv-import', fn (Request $request) => Limit::perMinute(3)->by(
            ($request->user()?->getAuthIdentifier() ?? $request->ip()).'|'.$request->ip()
        ));

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.url').'/reset-password?token='.$token.'&email='.$user->email;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
