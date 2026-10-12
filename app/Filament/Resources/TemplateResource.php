<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateResource\Pages\CreateTemplate;
use App\Filament\Resources\TemplateResource\Pages\EditTemplate;
use App\Filament\Resources\TemplateResource\Pages\ListTemplates;
use App\Filament\Resources\TemplateResource\Pages\ViewTemplate;
use App\Filament\Resources\TemplateResource\Schemas\TemplateForm;
use App\Filament\Resources\TemplateResource\Schemas\TemplateInfolist;
use App\Filament\Resources\TemplateResource\Tables\TemplatesTable;
use App\Models\Template;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'modelo';

    protected static ?string $pluralModelLabel = 'modelos';

    protected static ?string $navigationLabel = 'Modelos';

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return TemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'view' => ViewTemplate::route('/{record}'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }
}
