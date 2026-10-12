<?php

namespace App\Filament\Launchpad;

use App\Models\Notification;
use Filament\Launchpad\Launchpad\KpiResult;

/** Notificações: enviadas hoje, o que ainda está na fila e as falhas do dia. */
class EnviadasHojeKpi extends NotifikaKpi
{
    public function label(): string
    {
        return 'Enviadas hoje';
    }

    public function resolve(): KpiResult
    {
        $hoje = now()->startOfDay();

        $enviadas = Notification::query()->where('status', 'sent')->where('created_at', '>=', $hoje)->count();
        $fila = Notification::query()->where('status', 'queued')->count();
        $falhas = Notification::query()->where('status', 'failed')->where('created_at', '>=', $hoje)->count();

        $resultado = KpiResult::make($this->inteiro($enviadas))
            ->trend($this->contagem($fila, 'por enviar', 'por enviar'));

        return $falhas > 0
            ? $this->alerta($resultado, $this->contagem($falhas, 'falha hoje', 'falhas hoje'))
            : $resultado;
    }
}
