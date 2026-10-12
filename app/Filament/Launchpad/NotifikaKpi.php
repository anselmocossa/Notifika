<?php

namespace App\Filament\Launchpad;

use Filament\Launchpad\Launchpad\BaseKpiSource;
use Filament\Launchpad\Launchpad\KpiResult;

/**
 * Base dos KPIs dos cards do Launchpad: números em pt-MZ e um alerta
 * legível em modo claro e escuro.
 */
abstract class NotifikaKpi extends BaseKpiSource
{
    public function cacheFor(): ?int
    {
        return 60;
    }

    public function panels(): array
    {
        return ['admin'];
    }

    protected function inteiro(int|float $valor): string
    {
        return number_format($valor, 0, ',', ' ');
    }

    protected function contagem(int $n, string $singular, string $plural): string
    {
        return $this->inteiro($n).' '.($n === 1 ? $singular : $plural);
    }

    protected function alerta(KpiResult $resultado, string $texto): KpiResult
    {
        return $resultado->badge($texto, 'rgba(220, 38, 38, .15)', '#ef4444');
    }
}
