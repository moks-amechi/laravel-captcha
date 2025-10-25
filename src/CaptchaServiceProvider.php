<?php

namespace JustChill\LaravelCaptcha;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Blade;
use JustChill\LaravelCaptcha\Services\CaptchaService;
use JustChill\LaravelCaptcha\Http\Middleware\CaptchaMiddleware;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('captcha', function ($app) {
            return new CaptchaService();
        });
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'captcha');

        // Register middleware
        $this->app['router']->aliasMiddleware('captcha', CaptchaMiddleware::class);

        // Register validation rule
        Validator::extend('captcha', function ($attribute, $value, $parameters, $validator) {
            return app('captcha')->validate($value);
        });

        // Register Blade directive
        Blade::directive('captcha', function () {
            return "<?php echo app('captcha')->render(); ?>";
        });

        // Publish config if needed
        $this->publishes([
            __DIR__ . '/config/captcha.php' => config_path('captcha.php'),
        ], 'captcha-config');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
    }
}
