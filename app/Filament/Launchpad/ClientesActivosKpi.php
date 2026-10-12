<?php

namespace App\Filament\Launchpad;

use App\Models\Client;
use Filament\Launchpad\Launchpad\KpiResult;

/** Clientes: activos e o total da instalação. */
class ClientesActivosKpi extends NotifikaKpi
{
    public function label(): string
    {
        return 'Clientes activos';
    }

    public function resolve(): KpiResult
    {
        $activos = Client::query()->where('is_active', true)->count();
        $total = Client::query()->count();

        return KpiResult::make($this->inteiro($activos))
            ->trend($this->contagem($total, 'no total', 'no total'));
    }
}
