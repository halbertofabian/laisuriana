<?php

namespace App\Services\Operacion\Comercial;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ProductoImagenTemporalService
{
    private const CACHE_PREFIX = 'producto_imagen_temp:';
    private const TTL_MINUTES = 120;

    public function crearSesionCarga(?string $baseUrl = null): array
    {
        $token = (string) Str::uuid();
        $this->guardarEstado($token, [
            'token' => $token,
            'path' => null,
            'preview_url' => null,
            'original_name' => null,
            'uploaded_at' => null,
        ]);

        $mobilePath = route('operacion.catalogo_comercial.productos.imagen_movil', ['token' => $token], false);
        $mobileUrl = $this->buildAbsoluteUrl($mobilePath, $baseUrl);

        return [
            'token' => $token,
            'mobile_path' => $mobilePath,
            'mobile_url' => $mobileUrl,
            'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($mobileUrl),
            'estado' => $this->obtenerEstado($token),
        ];
    }

    public function obtenerEstado(string $token): array
    {
        $estado = Cache::get($this->cacheKey($token), [
            'token' => $token,
            'path' => null,
            'preview_url' => null,
            'original_name' => null,
            'uploaded_at' => null,
        ]);

        $estado['has_image'] = !empty($estado['path']);

        return $estado;
    }

    public function guardarDesdeMovil(string $token, UploadedFile $archivo): array
    {
        $this->eliminarArchivoTemporal($token);

        $path = $archivo->store('productos/temp', 'public');
        $estado = [
            'token' => $token,
            'path' => $path,
            'preview_url' => $this->storageRelativeUrl($path),
            'original_name' => $archivo->getClientOriginalName(),
            'uploaded_at' => Carbon::now()->toDateTimeString(),
        ];

        $this->guardarEstado($token, $estado);

        return $this->obtenerEstado($token);
    }

    public function moverATemporalFinal(string $token): ?array
    {
        $estado = $this->obtenerEstado($token);

        if (empty($estado['path']) || !Storage::disk('public')->exists($estado['path'])) {
            return null;
        }

        $extension = pathinfo($estado['path'], PATHINFO_EXTENSION) ?: 'jpg';
        $destino = 'productos/' . date('Y/m') . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->makeDirectory(dirname($destino));
        Storage::disk('public')->move($estado['path'], $destino);

        Cache::forget($this->cacheKey($token));

        return [
            'tipo' => 'archivo',
            'path' => $destino,
            'url' => null,
            'preview_url' => $this->storageRelativeUrl($destino),
        ];
    }

    public function guardarArchivoFinal(UploadedFile $archivo): array
    {
        $path = $archivo->store('productos/' . date('Y/m'), 'public');

        return [
            'tipo' => 'archivo',
            'path' => $path,
            'url' => null,
            'preview_url' => $this->storageRelativeUrl($path),
        ];
    }

    public function eliminarImagenPersistida(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function limpiarTemporal(string $token): void
    {
        $this->eliminarArchivoTemporal($token);
        Cache::forget($this->cacheKey($token));
    }

    private function eliminarArchivoTemporal(string $token): void
    {
        $estado = Cache::get($this->cacheKey($token));

        if (!empty($estado['path']) && Storage::disk('public')->exists($estado['path'])) {
            Storage::disk('public')->delete($estado['path']);
        }
    }

    private function guardarEstado(string $token, array $estado): void
    {
        Cache::put($this->cacheKey($token), $estado, now()->addMinutes(self::TTL_MINUTES));
    }

    private function cacheKey(string $token): string
    {
        return self::CACHE_PREFIX . $token;
    }

    private function storageRelativeUrl(string $path): string
    {
        return '/storage/' . ltrim($path, '/');
    }

    private function buildAbsoluteUrl(string $path, ?string $baseUrl = null): string
    {
        $base = rtrim((string) ($baseUrl ?: config('app.url')), '/');
        $path = '/' . ltrim($path, '/');

        if ($base === '') {
            return $path;
        }

        return $base . $path;
    }
}
