<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            // Nulo = template global (disponível a todos os clientes).
            $table->foreignUuid('client_id')->nullable()->after('id')->constrained('clients')->cascadeOnDelete();
            $table->string('channel')->default('email')->after('client_id');

            $table->index('client_id');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropIndex(['client_id']);
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['channel']);
        });
    }
};
