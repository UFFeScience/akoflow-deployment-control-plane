<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provider_credentials', function (Blueprint $table) {
            if (! Schema::hasColumn('provider_credentials', 'slug')) {
                $table->string('slug', 100)->nullable()->after('name');
                $table->unique(['provider_id', 'slug']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('provider_credentials', function (Blueprint $table) {
            $table->dropUnique(['provider_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
