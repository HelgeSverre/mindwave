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
        Schema::create('mindwave_span_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('span_id', 16);
            $table->string('type', 20)->comment('input, output, system');
            $table->json('messages');
            $table->timestamp('created_at');

            // Indexes
            $table->index('span_id', 'idx_span_id');

            // Foreign key constraint
            $table->foreign('span_id', 'fk_span_messages_span')
                ->references('span_id')
                ->on('mindwave_spans')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mindwave_span_messages');
    }
};
