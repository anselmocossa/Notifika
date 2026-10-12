<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    // A raiz do painel pertence ao Launchpad.
    protected static string $routePath = '/dashboard';

    protected static ?string $navigationLabel = 'Painel';

    protected static ?string $title = 'Painel';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    /**
     * Sem navegação lateral, a entrada é o card do Launchpad: o breadcrumb
     * mostra só a própria página.
     *
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [$this->getTitle()];
    }
}
