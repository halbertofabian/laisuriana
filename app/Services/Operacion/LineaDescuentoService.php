<?php

namespace App\Services\Operacion;

use InvalidArgumentException;

class LineaDescuentoService
{
    public function resolver(float $cantidad, float $precio, ?string $tipo, float $valor): array
    {
        $cantidad = round($cantidad, 2);
        $precio = round($precio, 2);
        $subtotal = round($cantidad * $precio, 2);
        $tipoNormalizado = $this->normalizarTipo($tipo);
        $valor = round($valor, 2);

        if ($subtotal <= 0 || $tipoNormalizado === 'ninguno' || $valor <= 0) {
            return [
                'descuento_tipo' => 'ninguno',
                'descuento_valor' => 0.0,
                'descuento_importe' => 0.0,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'descuento_porcentaje_equivalente' => 0.0,
            ];
        }

        if ($valor < 0) {
            throw new InvalidArgumentException('El descuento no puede ser negativo.');
        }

        if ($tipoNormalizado === 'porcentaje') {
            if ($valor > 100) {
                throw new InvalidArgumentException('El descuento por porcentaje no puede ser mayor a 100%.');
            }

            $descuentoImporte = round($subtotal * ($valor / 100), 2);

            return [
                'descuento_tipo' => 'porcentaje',
                'descuento_valor' => $valor,
                'descuento_importe' => $descuentoImporte,
                'subtotal' => $subtotal,
                'total' => round(max(0, $subtotal - $descuentoImporte), 2),
                'descuento_porcentaje_equivalente' => $valor,
            ];
        }

        if ($valor > $subtotal) {
            throw new InvalidArgumentException('El descuento no puede exceder el subtotal de la partida.');
        }

        $descuentoImporte = round(min($subtotal, $valor), 2);
        $porcentajeEquivalente = $subtotal > 0
            ? round(($descuentoImporte / $subtotal) * 100, 2)
            : 0.0;

        return [
            'descuento_tipo' => 'importe',
            'descuento_valor' => $valor,
            'descuento_importe' => $descuentoImporte,
            'subtotal' => $subtotal,
            'total' => round(max(0, $subtotal - $descuentoImporte), 2),
            'descuento_porcentaje_equivalente' => $porcentajeEquivalente,
        ];
    }

    public function normalizarTipo(?string $tipo): string
    {
        $tipo = strtolower(trim((string) $tipo));

        return match ($tipo) {
            'porcentaje' => 'porcentaje',
            'importe', 'cantidad_fija', 'cantidad', 'monto' => 'importe',
            default => 'ninguno',
        };
    }
}
