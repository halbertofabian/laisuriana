<?php

namespace Database\Seeders;

use App\Models\Atributo;
use App\Models\Categoria;
use App\Models\Concepto;
use App\Models\ExistenciaSucursal;
use App\Models\Linea;
use App\Models\Marca;
use App\Models\Motivo;
use App\Models\Producto;
use App\Models\ProductoAtributo;
use App\Models\ProductoSku;
use App\Models\SkuValorAtributo;
use App\Models\Sucursal;
use App\Models\UnidadMedida;
use App\Models\ValorAtributo;
use Illuminate\Database\Seeder;

class ComercialBaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedConceptos();
        $this->seedMotivos();

        $marcas = $this->seedMarcas();
        $lineas = $this->seedLineas();
        $categorias = $this->seedCategorias();
        $unidades = $this->seedUnidades();
        $atributos = $this->seedAtributos();
        $valores = $this->seedValores($atributos);

        $productos = $this->seedProductos($marcas, $lineas, $categorias, $unidades);

        $this->seedProductoAtributos($productos, $atributos);
        $this->seedSkusYCombinaciones($productos, $valores);
        $this->seedExistenciasPorSucursal();
    }

    private function seedConceptos(): void
    {
        $rows = [
            ['clave' => 'CPT_CANCELACION', 'nombre' => 'Cancelación'],
            ['clave' => 'CPT_DEVOLUCION', 'nombre' => 'Devolución'],
            ['clave' => 'CPT_AJUSTE', 'nombre' => 'Ajuste'],
        ];

        foreach ($rows as $row) {
            Concepto::query()->updateOrCreate(
                ['cpt_clave' => $row['clave']],
                ['cpt_nombre' => $row['nombre'], 'cpt_estatus' => 'activo']
            );
        }
    }

    private function seedMotivos(): void
    {
        $rows = [
            ['clave' => 'MTV_CANCELACION_CLIENTE', 'nombre' => 'Cancelación por solicitud del cliente'],
            ['clave' => 'MTV_DEVOLUCION_DANO', 'nombre' => 'Devolución por daño del producto'],
            ['clave' => 'MTV_AJUSTE_CONTEO', 'nombre' => 'Ajuste por diferencia de conteo'],
        ];

        foreach ($rows as $row) {
            Motivo::query()->updateOrCreate(
                ['mtv_clave' => $row['clave']],
                ['mtv_nombre' => $row['nombre'], 'mtv_estatus' => 'activo']
            );
        }
    }

    private function seedMarcas(): array
    {
        $rows = [
            ['clave' => 'MRC_MARCA_PROPIA', 'nombre' => 'Marca Propia'],
            ['clave' => 'MRC_NIKE', 'nombre' => 'Nike'],
            ['clave' => 'MRC_ADIDAS', 'nombre' => 'Adidas'],
            ['clave' => 'MRC_LEVIS', 'nombre' => "Levi's"],
        ];

        $map = [];
        foreach ($rows as $row) {
            $map[$row['clave']] = Marca::query()->updateOrCreate(
                ['mrc_clave' => $row['clave']],
                ['mrc_nombre' => $row['nombre'], 'mrc_estatus' => 'activo']
            );
        }

        return $map;
    }

    private function seedLineas(): array
    {
        $rows = [
            ['clave' => 'LNA_CASUAL', 'nombre' => 'Línea Casual'],
            ['clave' => 'LNA_DEPORTIVA', 'nombre' => 'Línea Deportiva'],
            ['clave' => 'LNA_PREMIUM', 'nombre' => 'Línea Premium'],
            ['clave' => 'LNA_PRIMAVERA_2026', 'nombre' => 'Colección Primavera 2026'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $map[$row['clave']] = Linea::query()->updateOrCreate(
                ['lna_clave' => $row['clave']],
                ['lna_nombre' => $row['nombre'], 'lna_estatus' => 'activo']
            );
        }

        return $map;
    }

    private function seedCategorias(): array
    {
        $rows = [
            ['clave' => 'CTG_ROPA', 'nombre' => 'Ropa'],
            ['clave' => 'CTG_TEXTIL', 'nombre' => 'Textil'],
            ['clave' => 'CTG_CALZADO', 'nombre' => 'Calzado'],
            ['clave' => 'CTG_ACCESORIOS', 'nombre' => 'Accesorios'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $map[$row['clave']] = Categoria::query()->updateOrCreate(
                ['ctg_clave' => $row['clave']],
                ['ctg_nombre' => $row['nombre'], 'ctg_estatus' => 'activo']
            );
        }

        return $map;
    }

    private function seedUnidades(): array
    {
        $rows = [
            ['clave' => 'UMD_PZA', 'nombre' => 'Pieza', 'codigo' => 'PZA'],
            ['clave' => 'UMD_M', 'nombre' => 'Metro', 'codigo' => 'M'],
            ['clave' => 'UMD_PAR', 'nombre' => 'Par', 'codigo' => 'PAR'],
            ['clave' => 'UMD_CJ', 'nombre' => 'Caja', 'codigo' => 'CJ'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $map[$row['clave']] = UnidadMedida::query()->updateOrCreate(
                ['umd_clave' => $row['clave']],
                ['umd_nombre' => $row['nombre'], 'umd_codigo' => $row['codigo'], 'umd_estatus' => 'activo']
            );
        }

        return $map;
    }

    private function seedAtributos(): array
    {
        $rows = [
            ['clave' => 'ATR_TALLA', 'nombre' => 'Talla', 'tipo' => 'seleccion'],
            ['clave' => 'ATR_COLOR', 'nombre' => 'Color', 'tipo' => 'seleccion'],
            ['clave' => 'ATR_MATERIAL', 'nombre' => 'Material', 'tipo' => 'seleccion'],
            ['clave' => 'ATR_ANCHO', 'nombre' => 'Ancho', 'tipo' => 'seleccion'],
            ['clave' => 'ATR_COMPOSICION', 'nombre' => 'Composición', 'tipo' => 'seleccion'],
            ['clave' => 'ATR_ESTILO', 'nombre' => 'Estilo', 'tipo' => 'seleccion'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $map[$row['clave']] = Atributo::query()->updateOrCreate(
                ['atr_clave' => $row['clave']],
                ['atr_nombre' => $row['nombre'], 'atr_tipo' => $row['tipo'], 'atr_estatus' => 'activo']
            );
        }

        return $map;
    }

    private function seedValores(array $atributos): array
    {
        $rows = [
            ['atr' => 'ATR_TALLA', 'clave' => 'VAT_TALLA_CH', 'valor' => 'CH'],
            ['atr' => 'ATR_TALLA', 'clave' => 'VAT_TALLA_M', 'valor' => 'M'],
            ['atr' => 'ATR_TALLA', 'clave' => 'VAT_TALLA_G', 'valor' => 'G'],
            ['atr' => 'ATR_COLOR', 'clave' => 'VAT_COLOR_AZUL_MARINO', 'valor' => 'Azul Marino'],
            ['atr' => 'ATR_COLOR', 'clave' => 'VAT_COLOR_NEGRO', 'valor' => 'Negro'],
            ['atr' => 'ATR_COLOR', 'clave' => 'VAT_COLOR_BLANCO', 'valor' => 'Blanco'],
            ['atr' => 'ATR_MATERIAL', 'clave' => 'VAT_MATERIAL_ALGODON', 'valor' => 'Algodón'],
            ['atr' => 'ATR_MATERIAL', 'clave' => 'VAT_MATERIAL_POLIESTER', 'valor' => 'Poliéster'],
            ['atr' => 'ATR_ANCHO', 'clave' => 'VAT_ANCHO_120', 'valor' => '1.20 m'],
            ['atr' => 'ATR_ANCHO', 'clave' => 'VAT_ANCHO_150', 'valor' => '1.50 m'],
            ['atr' => 'ATR_COMPOSICION', 'clave' => 'VAT_COMP_65_35', 'valor' => '65% Algodón / 35% Poliéster'],
            ['atr' => 'ATR_ESTILO', 'clave' => 'VAT_ESTILO_POLO', 'valor' => 'Polo'],
        ];

        $map = [];
        foreach ($rows as $row) {
            $atributo = $atributos[$row['atr']];

            $map[$row['clave']] = ValorAtributo::query()->updateOrCreate(
                ['vat_atr_id' => $atributo->atr_id, 'vat_clave' => $row['clave']],
                ['vat_valor' => $row['valor'], 'vat_estatus' => 'activo']
            );
        }

        return $map;
    }

    private function seedProductos(array $marcas, array $lineas, array $categorias, array $unidades): array
    {
        $rows = [
            [
                'codigo' => 'PRD-POLO-H-001',
                'nombre' => 'Playera Polo Hombre',
                'descripcion' => 'Playera tipo polo para caballero.',
                'tipo' => 'variable',
                'precio_base' => 349.90,
                'costo' => 189.45,
                'stock_minimo' => 3,
                'stock_maximo' => 20,
                'codigo_barras' => '7509000000011',
                'marca' => 'MRC_MARCA_PROPIA',
                'linea' => 'LNA_CASUAL',
                'categoria' => 'CTG_ROPA',
                'unidad' => 'UMD_PZA',
            ],
            [
                'codigo' => 'PRD-TELA-GAB-001',
                'nombre' => 'Tela Gabardina Azul',
                'descripcion' => 'Tela gabardina para confección.',
                'tipo' => 'variable',
                'precio_base' => 129.50,
                'costo' => 78.25,
                'stock_minimo' => 10,
                'stock_maximo' => 120,
                'codigo_barras' => '7509000000028',
                'marca' => 'MRC_MARCA_PROPIA',
                'linea' => 'LNA_PREMIUM',
                'categoria' => 'CTG_TEXTIL',
                'unidad' => 'UMD_M',
            ],
        ];

        $map = [];
        foreach ($rows as $row) {
            $map[$row['codigo']] = Producto::query()->updateOrCreate(
                ['prd_codigo' => $row['codigo']],
                [
                    'prd_nombre' => $row['nombre'],
                    'prd_descripcion' => $row['descripcion'],
                    'prd_tipo' => $row['tipo'],
                    'prd_precio_base' => $row['precio_base'],
                    'prd_costo' => $row['costo'],
                    'prd_stock_minimo' => $row['stock_minimo'],
                    'prd_stock_maximo' => $row['stock_maximo'],
                    'prd_codigo_barras' => $row['codigo_barras'],
                    'prd_mrc_id' => $marcas[$row['marca']]->mrc_id,
                    'prd_lna_id' => $lineas[$row['linea']]->lna_id,
                    'prd_ctg_id' => $categorias[$row['categoria']]->ctg_id,
                    'prd_umd_id' => $unidades[$row['unidad']]->umd_id,
                    'prd_estatus' => 'activo',
                ]
            );
        }

        return $map;
    }

    private function seedProductoAtributos(array $productos, array $atributos): void
    {
        $config = [
            'PRD-POLO-H-001' => ['ATR_TALLA', 'ATR_COLOR', 'ATR_MATERIAL', 'ATR_ESTILO'],
            'PRD-TELA-GAB-001' => ['ATR_ANCHO', 'ATR_COLOR', 'ATR_COMPOSICION'],
        ];

        foreach ($config as $productoCodigo => $atributosClaves) {
            $producto = $productos[$productoCodigo];

            foreach ($atributosClaves as $atrClave) {
                ProductoAtributo::query()->updateOrCreate(
                    ['pat_prd_id' => $producto->prd_id, 'pat_atr_id' => $atributos[$atrClave]->atr_id],
                    ['pat_estatus' => 'activo']
                );
            }
        }
    }

    private function seedSkusYCombinaciones(array $productos, array $valores): void
    {
        $rows = [
            [
                'producto' => 'PRD-POLO-H-001',
                'sku' => 'SKU-POLO-CH-AZM',
                'barcode' => '7509000100018',
                'nombre' => 'Polo CH Azul Marino',
                'precio' => 349.90,
                'stock_minimo' => 3,
                'stock_maximo' => 20,
                'valores' => ['VAT_TALLA_CH', 'VAT_COLOR_AZUL_MARINO', 'VAT_MATERIAL_ALGODON', 'VAT_ESTILO_POLO'],
            ],
            [
                'producto' => 'PRD-POLO-H-001',
                'sku' => 'SKU-POLO-M-AZM',
                'barcode' => '7509000100025',
                'nombre' => 'Polo M Azul Marino',
                'precio' => 349.90,
                'stock_minimo' => 3,
                'stock_maximo' => 20,
                'valores' => ['VAT_TALLA_M', 'VAT_COLOR_AZUL_MARINO', 'VAT_MATERIAL_ALGODON', 'VAT_ESTILO_POLO'],
            ],
            [
                'producto' => 'PRD-POLO-H-001',
                'sku' => 'SKU-POLO-G-NEG',
                'barcode' => '7509000100032',
                'nombre' => 'Polo G Negro',
                'precio' => 349.90,
                'stock_minimo' => 3,
                'stock_maximo' => 20,
                'valores' => ['VAT_TALLA_G', 'VAT_COLOR_NEGRO', 'VAT_MATERIAL_ALGODON', 'VAT_ESTILO_POLO'],
            ],
            [
                'producto' => 'PRD-TELA-GAB-001',
                'sku' => 'SKU-GAB-120-AZM',
                'barcode' => '7509000200014',
                'nombre' => 'Gabardina 1.20 Azul Marino',
                'precio' => 129.50,
                'stock_minimo' => 10,
                'stock_maximo' => 120,
                'valores' => ['VAT_ANCHO_120', 'VAT_COLOR_AZUL_MARINO', 'VAT_COMP_65_35'],
            ],
            [
                'producto' => 'PRD-TELA-GAB-001',
                'sku' => 'SKU-GAB-150-AZM',
                'barcode' => '7509000200021',
                'nombre' => 'Gabardina 1.50 Azul Marino',
                'precio' => 129.50,
                'stock_minimo' => 10,
                'stock_maximo' => 120,
                'valores' => ['VAT_ANCHO_150', 'VAT_COLOR_AZUL_MARINO', 'VAT_COMP_65_35'],
            ],
        ];

        foreach ($rows as $row) {
            $producto = $productos[$row['producto']];

            $sku = ProductoSku::query()->updateOrCreate(
                ['psk_prd_id' => $producto->prd_id, 'psk_codigo' => $row['sku']],
                [
                    'psk_codigo_barras' => $row['barcode'] ?? $row['sku'],
                    'psk_nombre' => $row['nombre'],
                    'psk_precio' => $row['precio'],
                    'psk_stock_minimo' => $row['stock_minimo'],
                    'psk_stock_maximo' => $row['stock_maximo'],
                    'psk_estatus' => 'activo',
                ]
            );

            foreach ($row['valores'] as $valorClave) {
                SkuValorAtributo::query()->updateOrCreate(
                    ['sva_psk_id' => $sku->psk_id, 'sva_vat_id' => $valores[$valorClave]->vat_id],
                    ['sva_estatus' => 'activo']
                );
            }
        }
    }

    private function seedExistenciasPorSucursal(): void
    {
        $sucursales = Sucursal::query()
            ->where('scl_estatus', 'activo')
            ->orderBy('scl_id')
            ->get(['scl_id']);

        if ($sucursales->isEmpty()) {
            return;
        }

        $skus = ProductoSku::query()
            ->where('psk_estatus', 'activo')
            ->orderBy('psk_id')
            ->get(['psk_id']);

        foreach ($skus as $indiceSku => $sku) {
            foreach ($sucursales as $indiceSucursal => $sucursal) {
                $cantidad = (($indiceSku + 1) * 4) + (($indiceSucursal + 1) * 3);

                ExistenciaSucursal::query()->updateOrCreate(
                    [
                        'exs_psk_id' => $sku->psk_id,
                        'exs_scl_id' => $sucursal->scl_id,
                    ],
                    [
                        'exs_existencia' => $cantidad,
                        'exs_estatus' => 'activo',
                    ]
                );
            }
        }
    }
}
