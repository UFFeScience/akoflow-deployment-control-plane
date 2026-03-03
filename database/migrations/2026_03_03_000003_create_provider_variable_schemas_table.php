<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_variable_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug');
            $table->string('section')->default('general');
            $table->string('name'); // field key, e.g. "gcp_project_id"
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('type')->default('string'); // string, select, secret, boolean, textarea, number
            $table->boolean('required')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->integer('position')->default(0);
            $table->text('options_json')->nullable(); // JSON array for select fields
            $table->string('default_value')->nullable();
            $table->timestamps();

            $table->index('provider_slug');
            $table->unique(['provider_slug', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_variable_schemas');
    }
};
