<?php

use App\Filament\Launchpad\ChangelogPorPublicarKpi;
use App\Filament\Launchpad\ClientesActivosKpi;
use App\Filament\Launchpad\EnviadasHojeKpi;
use App\Filament\Launchpad\EnviosTrintaDiasKpi;
use App\Filament\Launchpad\LaunchpadCardsKpi;
use App\Filament\Launchpad\ModelosActivosKpi;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\NotificationResource;
use App\Filament\Resources\TemplateResource;
use Filament\Changelog\Pages\ChangelogPage;
use Filament\Launchpad\Filament\Resources\SpaceResource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cards do Launchpad para as áreas do painel. Com a navegação lateral
 * desligada, o Launchpad é a única porta de entrada.
 */
return new class extends Migration
{
    /**
     * Secção => [título, subtítulo, ícone, tipo de destino, classe, fonte KPI].
     */
    private const SECCOES = [
        'Envio' => [
            ['Notificações', 'Enviadas hoje', 'heroicon-o-bell', 'resource', NotificationResource::class, EnviadasHojeKpi::class],
            ['Clientes', 'Clientes activos', 'heroicon-o-building-office-2', 'resource', ClientResource::class, ClientesActivosKpi::class],
            ['Modelos', 'Modelos activos', 'heroicon-o-document-text', 'resource', TemplateResource::class, ModelosActivosKpi::class],
        ],
        'Acompanhamento' => [
            ['Painel', 'Envios nos últimos 30 dias', 'heroicon-o-chart-bar', 'page', Dashboard::class, EnviosTrintaDiasKpi::class],
            ['Changelog', 'Alterações por publicar', 'heroicon-o-megaphone', 'page', ChangelogPage::class, ChangelogPorPublicarKpi::class],
        ],
        'Configuração' => [
            ['Launchpad', 'Cards configurados', 'heroicon-o-squares-2x2', 'resource', SpaceResource::class, LaunchpadCardsKpi::class],
        ],
    ];

    public function up(): void
    {
        $pageId = DB::table('launchpad_pages')
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->value('id');

        if ($pageId === null) {
            return;
        }

        $now = now();

        DB::table('launchpad_spaces')
            ->where('is_default', true)
            ->where('label', 'Home')
            ->update(['label' => 'Início', 'updated_at' => $now]);

        DB::table('launchpad_pages')
            ->where('id', $pageId)
            ->where('label', 'Home')
            ->update(['label' => 'Início', 'updated_at' => $now]);

        DB::table('launchpad_sections')
            ->where('page_id', $pageId)
            ->where('title', 'Home')
            ->whereNull('user_id')
            ->whereNull('tenant_id')
            ->update(['title' => array_key_first(self::SECCOES), 'updated_at' => $now]);

        $ordemSeccao = 0;

        foreach (self::SECCOES as $titulo => $cards) {
            $sectionId = DB::table('launchpad_sections')
                ->where('page_id', $pageId)
                ->where('title', $titulo)
                ->whereNull('user_id')
                ->whereNull('tenant_id')
                ->value('id')
                ?? DB::table('launchpad_sections')->insertGetId([
                    'page_id' => $pageId,
                    'title' => $titulo,
                    'sort' => $ordemSeccao,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            DB::table('launchpad_sections')->where('id', $sectionId)->update(['sort' => $ordemSeccao++]);

            foreach ($cards as $ordem => [$title, $subtitle, $icon, $targetType, $target, $kpi]) {
                $campos = [
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'icon' => $icon,
                    'type' => 'kpi',
                    'kpi_source' => $kpi::key(),
                    'target_type' => $targetType,
                    'target_value' => $target,
                    'sort' => $ordem,
                    'updated_at' => $now,
                ];

                $cardId = DB::table('launchpad_cards')
                    ->where('target_value', $target)
                    ->whereNull('tenant_id')
                    ->value('id');

                if ($cardId === null) {
                    $cardId = DB::table('launchpad_cards')->insertGetId($campos + ['created_at' => $now]);
                } else {
                    DB::table('launchpad_cards')->where('id', $cardId)->update($campos);
                }

                $ligado = DB::table('launchpad_section_card')
                    ->where('section_id', $sectionId)
                    ->where('card_id', $cardId)
                    ->exists();

                if (! $ligado) {
                    DB::table('launchpad_section_card')->insert([
                        'section_id' => $sectionId,
                        'card_id' => $cardId,
                        'sort' => $ordem,
                        'is_pinned' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $targets = collect(self::SECCOES)->flatten(1)->pluck(4)->all();

        $cardIds = DB::table('launchpad_cards')
            ->whereIn('target_value', $targets)
            ->whereNull('tenant_id')
            ->pluck('id');

        DB::table('launchpad_section_card')->whereIn('card_id', $cardIds)->delete();
        DB::table('launchpad_cards')->whereIn('id', $cardIds)->delete();

        $titulos = array_keys(self::SECCOES);
        $primeira = array_shift($titulos);

        DB::table('launchpad_sections')
            ->whereIn('title', $titulos)
            ->whereNull('user_id')
            ->whereNull('tenant_id')
            ->delete();

        DB::table('launchpad_sections')
            ->where('title', $primeira)
            ->whereNull('user_id')
            ->whereNull('tenant_id')
            ->update(['title' => 'Home']);

        DB::table('launchpad_spaces')->where('label', 'Início')->where('is_default', true)->update(['label' => 'Home']);
        DB::table('launchpad_pages')->where('label', 'Início')->update(['label' => 'Home']);
    }
};
