<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table): void {
            $table->id();
            $table->string('player_name');
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->string('status', 20);
            $table->string('result', 20)->nullable();
            $table->text('current_progression_with_gap');
            $table->text('current_progression_full');
            $table->integer('current_missing_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
