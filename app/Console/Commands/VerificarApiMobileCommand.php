<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;
use Throwable;

class VerificarApiMobileCommand extends Command
{
    protected $signature = 'mobile:verificar-api';

    protected $description = 'Verifica que el API de Suriana Vendedor esté listo para recibir la app Android';

    public function handle(): int
    {
        $checks = [
            'Tabla de tokens Sanctum' => fn (): bool => Schema::hasTable('personal_access_tokens'),
            'Identificador de reintento móvil' => fn (): bool => Schema::hasColumn('tbl_pedidos_piso_pdp', 'pdp_mobile_request_id'),
            'Ruta de diagnóstico' => fn (): bool => $this->routeExists('GET', 'api/v1/mobile/health'),
            'Ruta de acceso móvil' => fn (): bool => $this->routeExists('POST', 'api/v1/mobile/auth/login'),
            'Pedidos protegidos por Sanctum' => fn (): bool => $this->floorOrdersUseRequiredMiddleware(),
            'CORS incluye el API' => fn (): bool => in_array('api/*', config('cors.paths', []), true),
            'CORS permite Capacitor Android' => fn (): bool => in_array('http://localhost', config('cors.allowed_origins', []), true),
            'URL HTTPS en producción' => fn (): bool => ! app()->environment('production')
                || parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https',
        ];

        $failed = [];
        foreach ($checks as $label => $check) {
            try {
                $passed = $check();
            } catch (Throwable $exception) {
                $passed = false;
                $failed[] = $label.': '.$exception->getMessage();
            }

            $this->components->twoColumnDetail($label, $passed ? '<fg=green>OK</>' : '<fg=red>FALTA</>');
            if (! $passed && ! collect($failed)->contains(fn (string $message): bool => str_starts_with($message, $label.':'))) {
                $failed[] = $label;
            }
        }

        if ($failed !== []) {
            $this->newLine();
            $this->error('El API móvil todavía no está listo.');
            foreach ($failed as $failure) {
                $this->line(' - '.$failure);
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('API móvil listo para Suriana Vendedor.');

        return self::SUCCESS;
    }

    private function routeExists(string $method, string $uri): bool
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->contains(fn (Route $route): bool => in_array($method, $route->methods(), true) && $route->uri() === $uri);
    }

    private function floorOrdersUseRequiredMiddleware(): bool
    {
        $route = collect(RouteFacade::getRoutes()->getRoutes())
            ->first(fn (Route $candidate): bool => in_array('POST', $candidate->methods(), true)
                && $candidate->uri() === 'api/v1/mobile/floor-orders');

        if (! $route) {
            return false;
        }

        $middleware = $route->gatherMiddleware();

        return in_array('auth:sanctum', $middleware, true)
            && in_array('abilities:mobile:orders', $middleware, true);
    }
}
