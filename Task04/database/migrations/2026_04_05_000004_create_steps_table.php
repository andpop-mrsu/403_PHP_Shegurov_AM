<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->integer('step_number');
            $table->dateTime('answered_at');
            $table->text('progression_with_gap');
            $table->text('progression_full');
            $table->integer('missing_number');
            $table->string('user_answer');
            $table->boolean('is_correct');

            $table->unique(['game_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};
