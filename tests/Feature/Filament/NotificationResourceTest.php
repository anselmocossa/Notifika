<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Filament\Resources\NotificationResource\Pages\ViewNotification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationResourceTest extends TestCase
{
    use RefreshDatabase;

    private function createNotification(): Notification
    {
        return Notification::create([
            'external_id' => Notification::generateExternalId(),
            'to' => 'destinatario@example.com',
            'subject' => 'Assunto de teste',
            'content' => 'Conteúdo de teste',
            'status' => 'queued',
        ]);
    }

    public function test_list_page_renders_for_authenticated_admin(): void
    {
        $admin = User::factory()->create();
        $this->createNotification();
        $this->createNotification();

        $this->actingAs($admin);

        Livewire::test(ListNotifications::class)->assertSuccessful();
    }

    public function test_view_page_renders_for_authenticated_admin(): void
    {
        $admin = User::factory()->create();
        $notification = $this->createNotification();

        $this->actingAs($admin);

        Livewire::test(ViewNotification::class, [
            'record' => $notification->getRouteKey(),
        ])->assertSuccessful();
    }
}
