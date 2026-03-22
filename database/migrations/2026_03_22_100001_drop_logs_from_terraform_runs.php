<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('terraform_runs', function (Blueprint $table) {
            $table->dropColumn('logs');
        });
    }

    public function down(): void
    {
        Schema::table('terraform_runs', function (Blueprint $table) {
            $table->longText('logs')->nullable()->after('output_json');
        });
    }
};
