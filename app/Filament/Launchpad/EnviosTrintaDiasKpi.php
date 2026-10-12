<?php

namespace App\Filament\Launchpad;

use App\Models\Notification;
use Filament\Launchpad\Launchpad\KpiResult;

/** Painel: volume dos últimos 30 dias e as falhas nesse período. */
class EnviosTrintaDiasKpi extends NotifikaKpi
{
    public function label(): string
    {
        return 'Envios em 30 dias';
    }

    public function resolve(): KpiResult
    {
        $desde = now()->subDays(30);

        $total = Notification::query()->where('created_at', '>=', $desde)->count();
        $falhas = Notification::query()->where('status', 'failed')->where('created_at', '>=', $desde)->count();

        $resultado = KpiResult::make($this->inteiro($total))
            ->trend('Últimos 30 dias');

        return $falhas > 0
            ? $this->alerta($resultado, $this->contagem($falhas, 'falha', 'falhas'))
            : $resultado;
    }
}
