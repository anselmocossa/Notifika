<?php

namespace Tests\Feature;

use App\Filament\Launchpad\ClientesActivosKpi;
use App\Filament\Launchpad\EnviadasHojeKpi;
use App\Filament\Launchpad\ModelosActivosKpi;
use App\Models\Client;
use App\Models\Notification;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchpadKpisTest extends TestCase
{
    use RefreshDatabase;

    public function test_enviadas_hoje_conta_a_fila_e_alerta_falhas(): void
    {
        Notification::create([
            'external_id' => Notification::generateExternalId(),
            'to' => 'a@example.com',
            'content' => 'Enviada',
            'status' => 'sent',
        ]);
        Notification::create([
            'external_id' => Notification::generateExternalId(),
            'to' => 'b@example.com',
            'content' => 'Na fila',
            'status' => 'queued',
        ]);
        Notification::create([
            'external_id' => Notification::generateExternalId(),
            'to' => 'c@example.com',
            'content' => 'Falhou',
            'status' => 'failed',
        ]);

        $kpi = (new EnviadasHojeKpi)->resolve();

        $this->assertSame('1', $kpi->getValue());
        $this->assertSame('1 por enviar', $kpi->getTrend());
        $this->assertSame('1 falha hoje', $kpi->getBadge());
    }

    public function test_clientes_activos_separam_o_total(): void
    {
        Client::factory()->create(['is_active' => true]);
        Client::factory()->create(['is_active' => false]);

        $kpi = (new ClientesActivosKpi)->resolve();

        $this->assertSame('1', $kpi->getValue());
        $this->assertSame('2 no total', $kpi->getTrend());
    }

    public function test_modelos_activos_separam_o_total(): void
    {
        Template::create([
            'name' => 'activo',
            'display_name' => 'Activo',
            'content' => 'Olá',
            'is_active' => true,
        ]);
        Template::create([
            'name' => 'inactivo',
            'display_name' => 'Inactivo',
            'content' => 'Olá',
            'is_active' => false,
        ]);

        $kpi = (new ModelosActivosKpi)->resolve();

        $this->assertSame('1', $kpi->getValue());
        $this->assertSame('2 no total', $kpi->getTrend());
    }
}
