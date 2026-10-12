<?php

namespace App\Filament\Launchpad;

use Filament\Changelog\Support\ChangelogSource;
use Filament\Launchpad\Launchpad\KpiResult;

/** Changelog: alterações por publicar e a data da última versão. */
class ChangelogPorPublicarKpi extends NotifikaKpi
{
    public function label(): string
    {
        return 'Changelog por publicar';
    }

    public function resolve(): KpiResult
    {
        $entradas = ChangelogSource::entries();

        $porPublicar = $entradas->where('is_released', false)->count();
        $ultima = $entradas->where('is_released', true)->pluck('released_at')->filter()->max();

        $data = $ultima instanceof \DateTimeInterface
            ? $ultima->format('d/m/Y')
            : (is_string($ultima) ? $ultima : null);

        return KpiResult::make($this->inteiro($porPublicar))
            ->trend($data ? 'Última: '.$data : 'Nenhuma versão publicada');
    }
}
