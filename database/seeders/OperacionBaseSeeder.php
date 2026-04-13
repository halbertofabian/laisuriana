<?php

namespace Database\Seeders;

use App\Models\TipoAlmacen;
use Illuminate\Database\Seeder;

class OperacionBaseSeeder extends Seeder
{
    public function run(): void
    {
        $tiposBase = [
            ['clave' => 'producto_facturado', 'nombre' => 'Almacén de Producto Facturado', 'descripcion' => 'Inventario disponible para venta facturada.'],
            ['clave' => 'producto_no_facturado', 'nombre' => 'Almacén de Producto No Facturado', 'descripcion' => 'Inventario para operación no facturada y regularización.'],
            ['clave' => 'principal', 'nombre' => 'Principal', 'descripcion' => 'Almacén principal de la sucursal.'],
            ['clave' => 'piso_venta', 'nombre' => 'Piso de Venta', 'descripcion' => 'Zona de inventario disponible para venta directa.'],
            ['clave' => 'bodega', 'nombre' => 'Bodega', 'descripcion' => 'Almacén de resguardo y reposición interna.'],
            ['clave' => 'devoluciones', 'nombre' => 'Devoluciones', 'descripcion' => 'Almacén para retornos y revisión de mercancía.'],
            ['clave' => 'transito', 'nombre' => 'Tránsito', 'descripcion' => 'Almacén temporal para mercancía en movimiento.'],
        ];

        foreach ($tiposBase as $tipo) {
            $registro = TipoAlmacen::query()
                ->withDeleted()
                ->where(function ($query) use ($tipo): void {
                    $query->where('tal_clave', $tipo['clave'])
                        ->orWhere('tal_nombre', $tipo['nombre']);
                })
                ->first();

            if ($registro) {
                $registro->update([
                    'tal_clave' => $tipo['clave'],
                    'tal_nombre' => $tipo['nombre'],
                    'tal_descripcion' => $tipo['descripcion'],
                    'tal_estatus' => 'activo',
                    'tal_deleted' => false,
                    'tal_deleted_at' => null,
                ]);
                continue;
            }

            TipoAlmacen::query()->create([
                'tal_clave' => $tipo['clave'],
                'tal_nombre' => $tipo['nombre'],
                'tal_descripcion' => $tipo['descripcion'],
                'tal_estatus' => 'activo',
                'tal_deleted' => false,
                'tal_deleted_at' => null,
            ]);
        }
    }
}
