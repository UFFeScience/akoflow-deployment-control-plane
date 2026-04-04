<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_credentials', function (Blueprint $table) {
            $table->string('health_status')->default('UNHEALTHY')->after('is_active');
            $table->text('health_message')->nullable()->after('health_status');
            $table->timestamp('last_health_check_at')->nullable()->after('health_message');

            $table->index('health_status');
        });
    }

    public function down(): void
    {
        Schema::table('provider_credentials', function (Blueprint $table) {
            $table->dropIndex(['health_status']);
            $table->dropColumn(['health_status', 'health_message', 'last_health_check_at']);
        });
    }
};
