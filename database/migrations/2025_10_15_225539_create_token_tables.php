<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('token_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->timestamps();
        });

        Schema::create('refresh_token', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('token', 600);
            $table->dateTime('token_expiration')->nullable();

            $table->dateTime('used_at')->nullable();
            $table->unsignedBigInteger('refresh_id')->nullable();

            $table->unsignedBigInteger('token_session_id');
            $table->foreign('token_session_id')->references('id')->on('token_sessions')->onDelete('CASCADE');

            $table->dateTime('created_at')->default(DB::raw('current_timestamp'));
        });

        Schema::create('auth_token', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('token', 600);
            $table->dateTime('token_expiration')->nullable();

            $table->bigInteger('refresh_id')->unsigned()->nullable();
            $table->foreign('refresh_id')->references('id')->on('refresh_token')->onDelete('CASCADE');

            $table->unsignedBigInteger('token_session_id')->nullable();
            $table->foreign('token_session_id')->nullable()->references('id')->on('token_sessions')->onDelete('CASCADE');

            $table->dateTime('created_at')->default(DB::raw('current_timestamp'));
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_token');
        Schema::dropIfExists('auth_token');
        Schema::dropIfExists('token_sessions');
    }
};
