<?php

namespace App\Http\Controllers\Operacion;

use App\Http\Controllers\Controller;
use App\Models\PosTicketConfiguracion;
use App\Services\Operacion\TicketLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TicketPersonalizacionController extends Controller
{
    public function index(): View
    {
        $config = PosTicketConfiguracion::singleton();

        return view('operacion.ticket_personalizacion.index', [
            'config' => $config,
            'logoUrl' => $config->ptc_logo_path ? '/storage/' . ltrim($config->ptc_logo_path, '/') : null,
            'preview' => [
                'empresa' => 'Matriz Comitán',
                'fecha' => now()->format('d/m/Y H:i'),
                'almacen' => 'I. Suriana',
                'cliente' => 'Cliente de ejemplo',
                'articulos' => 3,
                'vendedores' => 'María López, Juan Pérez',
                'folio' => 'TCK-000123',
                'items' => [
                    ['nombre' => 'Playera chivas / CH', 'vendedor' => 'María López', 'cantidad' => '1', 'importe' => '$500.00'],
                    ['nombre' => 'Playera chivas / G', 'vendedor' => 'Juan Pérez', 'cantidad' => '2', 'importe' => '$1,000.00'],
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'texto_encabezado' => ['nullable', 'string', 'max:4000'],
            'texto_pie' => ['nullable', 'string', 'max:4000'],
        ]);

        $config = PosTicketConfiguracion::singleton();
        $payload = [
            'ptc_texto_encabezado' => trim((string) ($data['texto_encabezado'] ?? '')) ?: null,
            'ptc_texto_pie' => trim((string) ($data['texto_pie'] ?? '')) ?: null,
            'ptc_updated_by_usr_id' => $request->user()?->usr_id,
        ];

        if (!$config->ptc_created_by_usr_id) {
            $payload['ptc_created_by_usr_id'] = $request->user()?->usr_id;
        }

        if ($request->hasFile('logo')) {
            if ($config->ptc_logo_path) {
                Storage::disk('public')->delete($config->ptc_logo_path);
            }

            $payload['ptc_logo_path'] = app(TicketLogoService::class)->store($request->file('logo'));
        }

        $config->fill($payload);
        $config->save();

        return redirect()
            ->route('operacion.ticket_personalizacion.index')
            ->with('success', 'Personalización del ticket actualizada correctamente.');
    }
}
