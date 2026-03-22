<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->foreignId('provider_credential_id')
                ->nullable()
                ->after('provider_id')
                ->constrained('provider_credentials')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropForeign(['provider_credential_id']);
            $table->dropColumn('provider_credential_id');
        });
    }
};
