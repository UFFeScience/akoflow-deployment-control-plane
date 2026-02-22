<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provisioned_instances', function (Blueprint $table) {
            $table->foreignId('instance_group_id')->nullable()->after('cluster_id')->constrained('instance_groups')->nullOnDelete();
            $table->index('instance_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('provisioned_instances', function (Blueprint $table) {
            $table->dropForeign(['instance_group_id']);
            $table->dropColumn('instance_group_id');
        });
    }
};
