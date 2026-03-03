<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('provider_credential_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_credential_id')->constrained('provider_credentials')->onDelete('cascade');
            $table->string('field_key');
            $table->text('field_value')->nullable();
            $table->timestamps();

            $table->unique(['provider_credential_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_credential_values');
        Schema::dropIfExists('provider_credentials');
    }
};
