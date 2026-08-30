<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->unique()->constrained('tasks')->cascadeOnDelete();
            $table->text('summary');
            $table->string('complexity');
            $table->integer('estimated_hours');
            $table->json('steps');
            $table->json('risks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_analyses');
    }
};
