<?php

namespace App\Filament\Launchpad;

use Filament\Launchpad\Launchpad\KpiResult;
use Filament\Launchpad\Models\Card;
use Filament\Launchpad\Models\Section;

/** Launchpad: cards e secções configurados. */
class LaunchpadCardsKpi extends NotifikaKpi
{
    public function label(): string
    {
        return 'Cards do Launchpad';
    }

    public function resolve(): KpiResult
    {
        return KpiResult::make($this->inteiro(Card::query()->count()))
            ->trend($this->contagem(Section::query()->whereNull('user_id')->count(), 'secção', 'secções'));
    }
}
