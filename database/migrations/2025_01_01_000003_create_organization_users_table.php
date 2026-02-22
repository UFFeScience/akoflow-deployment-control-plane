<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->string('role')->default('member'); // owner, admin, member, viewer
            $table->timestamps();

            $table->unique(['user_id', 'organization_id']);
            $table->index('organization_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_users');
    }
};
