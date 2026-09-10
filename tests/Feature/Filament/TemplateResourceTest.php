<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\TemplateResource\Pages\CreateTemplate;
use App\Filament\Resources\TemplateResource\Pages\EditTemplate;
use App\Filament\Resources\TemplateResource\Pages\ListTemplates;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TemplateResourceTest extends TestCase
{
    use RefreshDatabase;

    private function createTemplate(): Template
    {
        return Template::create([
            'name' => 'payment_confirmed',
            'display_name' => 'Confirmação de Pagamento',
            'subject' => 'Pagamento confirmado',
            'content' => 'Olá {{ name }}, o seu pagamento foi confirmado.',
            'is_active' => true,
        ]);
    }

    public function test_list_page_renders_for_authenticated_admin(): void
    {
        $admin = User::factory()->create();
        $this->createTemplate();

        $this->actingAs($admin);

        Livewire::test(ListTemplates::class)->assertSuccessful();
    }

    public function test_create_page_renders_for_authenticated_admin(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreateTemplate::class)->assertSuccessful();
    }

    public function test_edit_page_renders_for_authenticated_admin(): void
    {
        $admin = User::factory()->create();
        $template = $this->createTemplate();

        $this->actingAs($admin);

        Livewire::test(EditTemplate::class, [
            'record' => $template->getRouteKey(),
        ])->assertSuccessful();
    }

    public function test_can_create_template_through_the_form(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(CreateTemplate::class)
            ->fillForm([
                'name' => 'welcome_email',
                'display_name' => 'Email de Boas-vindas',
                'subject' => 'Bem-vindo!',
                'content' => 'Olá {{ name }}, bem-vindo à Notifika.',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('templates', [
            'name' => 'welcome_email',
        ]);
    }
}
