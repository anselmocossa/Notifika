<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Models\Client;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationResourceFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function notification(Client $client, string $channel): Notification
    {
        return Notification::create([
            'external_id' => Notification::generateExternalId(),
            'client_id' => $client->id,
            'channel' => $channel,
            'to' => $channel === 'sms' ? '+258841234567' : 'destinatario@example.com',
            'content' => 'Conteúdo de teste',
            'status' => 'sent',
        ]);
    }

    public function test_can_filter_by_channel(): void
    {
        $admin = User::factory()->create();
        $client = Client::factory()->create();

        $email = $this->notification($client, 'email');
        $sms = $this->notification($client, 'sms');

        $this->actingAs($admin);

        Livewire::test(ListNotifications::class)
            ->assertCanSeeTableRecords([$email, $sms])
            ->filterTable('channel', 'sms')
            ->assertCanSeeTableRecords([$sms])
            ->assertCanNotSeeTableRecords([$email]);
    }

    public function test_can_filter_by_client(): void
    {
        $admin = User::factory()->create();
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();

        $fromA = $this->notification($clientA, 'email');
        $fromB = $this->notification($clientB, 'email');

        $this->actingAs($admin);

        Livewire::test(ListNotifications::class)
            ->assertCanSeeTableRecords([$fromA, $fromB])
            ->filterTable('client_id', $clientA->id)
            ->assertCanSeeTableRecords([$fromA])
            ->assertCanNotSeeTableRecords([$fromB]);
    }
}
