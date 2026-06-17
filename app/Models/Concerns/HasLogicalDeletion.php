<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLogicalDeletion
{
    public static function bootHasLogicalDeletion(): void
    {
        static::addGlobalScope('logical_not_deleted', function (Builder $builder): void {
            $builder
                ->where($builder->qualifyColumn(static::logicalDeletedColumn()), false)
                ->whereNull($builder->qualifyColumn(static::logicalDeletedAtColumn()));
        });
    }

    public function scopeWithDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('logical_not_deleted');
    }

    public function scopeOnlyDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('logical_not_deleted')
            ->where($this->qualifyColumn(static::logicalDeletedColumn()), true);
    }

    public function marcarComoEliminado(): bool
    {
        $this->prepararCamposUnicosParaEliminacion();

        return $this->forceFill([
            static::logicalDeletedColumn() => true,
            static::logicalDeletedAtColumn() => now(),
        ])->save();
    }

    protected function prepararCamposUnicosParaEliminacion(): void
    {
        $columnas = defined('static::LOGICAL_DELETION_UNIQUE_COLUMNS')
            ? constant('static::LOGICAL_DELETION_UNIQUE_COLUMNS')
            : [];

        if (!is_array($columnas) || $columnas === []) {
            return;
        }

        foreach ($columnas as $columna => $longitudMaxima) {
            if (is_int($columna)) {
                $columna = $longitudMaxima;
                $longitudMaxima = null;
            }

            $valor = $this->getAttribute($columna);

            if (!is_string($valor) || $valor === '') {
                continue;
            }

            $sufijo = '__d' . $this->getKey();
            $maximo = is_int($longitudMaxima)
                ? max(1, $longitudMaxima - mb_strlen($sufijo))
                : max(1, 190 - mb_strlen($sufijo));
            $this->setAttribute($columna, mb_substr($valor, 0, $maximo) . $sufijo);
        }
    }

    protected static function logicalDeletedColumn(): string
    {
        return defined('static::LOGICAL_DELETED_COLUMN') ? static::LOGICAL_DELETED_COLUMN : 'deleted';
    }

    protected static function logicalDeletedAtColumn(): string
    {
        return defined('static::LOGICAL_DELETED_AT_COLUMN') ? static::LOGICAL_DELETED_AT_COLUMN : 'deleted_at';
    }
}
