<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_account_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('id_user');
            $table->string('type', 50);
            $table->string('title', 150);
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['id_user', 'created_at']);
            $table->index(['id_user', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_account_notifications');
    }
};