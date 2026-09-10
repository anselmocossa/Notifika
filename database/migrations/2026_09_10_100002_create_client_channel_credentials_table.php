<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_channel_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            // 'sms' ou 'whatsapp' nesta leva; 'email' não precisa (usa o mailer da instalação).
            $table->string('channel');
            $table->string('driver');
            // Cifradas em repouso — cast encrypted:array no modelo (CA-12).
            $table->text('credentials');
            // Número, sender ID alfanumérico (SMS) ou phone_number_id (Meta WhatsApp).
            $table->string('sender')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['client_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_channel_credentials');
    }
};
