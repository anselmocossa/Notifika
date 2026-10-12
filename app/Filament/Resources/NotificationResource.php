<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Filament\Resources\NotificationResource\Pages\ViewNotification;
use App\Filament\Resources\NotificationResource\Schemas\NotificationInfolist;
use App\Filament\Resources\NotificationResource\Tables\NotificationsTable;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|\UnitEnum|null $navigationGroup = 'Notificações';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'notificação';

    protected static ?string $pluralModelLabel = 'notificações';

    protected static ?string $navigationLabel = 'Notificações';

    protected static ?string $recordTitleAttribute = 'external_id';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return NotificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotifications::route('/'),
            'view' => ViewNotification::route('/{record}'),
        ];
    }
}
