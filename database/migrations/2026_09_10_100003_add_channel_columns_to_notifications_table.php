<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Nullable a nível de BD para não partir instalações com notificações antigas;
            // a aplicação preenche sempre client_id e channel nas novas notificações.
            $table->foreignUuid('client_id')->nullable()->after('id')->constrained('clients')->nullOnDelete();
            $table->string('channel')->default('email')->after('client_id');
            $table->string('driver')->nullable()->after('channel');
            $table->string('provider_message_id')->nullable()->after('driver');
            $table->string('encoding')->nullable()->after('provider_message_id');
            $table->unsignedInteger('segments')->nullable()->after('encoding');
            $table->decimal('cost', 10, 4)->nullable()->after('segments');
            $table->string('cost_currency', 3)->nullable()->after('cost');

            $table->index('client_id');
            $table->index('channel');
            // Único por par (driver, provider_message_id): dois drivers podem, em teoria,
            // devolver o mesmo identificador sem colidir entre si.
            $table->unique(['driver', 'provider_message_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique(['driver', 'provider_message_id']);
            $table->dropIndex(['channel']);
            $table->dropIndex(['client_id']);
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn([
                'channel', 'driver', 'provider_message_id', 'encoding', 'segments', 'cost', 'cost_currency',
            ]);
        });
    }
};
