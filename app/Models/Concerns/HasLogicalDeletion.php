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
        return $this->forceFill([
            static::logicalDeletedColumn() => true,
            static::logicalDeletedAtColumn() => now(),
        ])->save();
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
