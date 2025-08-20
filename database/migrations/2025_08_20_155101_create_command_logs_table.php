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
        Schema::create('command_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('exam_session_id')->constrained('exam_sessions')->onDelete('cascade');
        $table->integer('step_number');
        $table->text('command');
        $table->boolean('is_correct')->default(false);
        $table->text('response_output')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_logs');
    }
};
