<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_metadata', function (Blueprint $table) {
            $table->id();

            $table->foreignId('environment_id')
                ->constrained('environments')
                ->cascadeOnDelete();

            $table->string('meta_key', 100);
            $table->text('meta_value')->nullable();

            $table->timestamps();

            $table->index('environment_id');
            $table->index('meta_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_metadata');
    }
};
