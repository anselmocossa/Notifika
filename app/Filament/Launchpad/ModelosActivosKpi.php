<?php

namespace App\Filament\Launchpad;

use App\Models\Template;
use Filament\Launchpad\Launchpad\KpiResult;

/** Modelos: activos e o total. */
class ModelosActivosKpi extends NotifikaKpi
{
    public function label(): string
    {
        return 'Modelos activos';
    }

    public function resolve(): KpiResult
    {
        $activos = Template::query()->where('is_active', true)->count();
        $total = Template::query()->count();

        return KpiResult::make($this->inteiro($activos))
            ->trend($this->contagem($total, 'no total', 'no total'));
    }
}
