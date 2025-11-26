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
        Schema::create('mindwave_spans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('trace_id', 32);
            $table->char('span_id', 16)->unique();
            $table->char('parent_span_id', 16)->nullable();
            $table->string('name', 500);
            $table->string('kind', 20)->comment('client, server, internal, producer, consumer');
            $table->unsignedBigInteger('start_time')->comment('Start time in nanoseconds');
            $table->unsignedBigInteger('end_time')->nullable()->comment('End time in nanoseconds');
            $table->unsignedBigInteger('duration')->nullable()->comment('Duration in nanoseconds');

            // GenAI specific attributes
            $table->string('operation_name', 50)->nullable()->comment('chat, embeddings, execute_tool');
            $table->string('provider_name', 50)->nullable()->comment('openai, anthropic, etc.');
            $table->string('request_model', 100)->nullable();
            $table->string('response_model', 100)->nullable();

            // Token usage
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('cache_read_tokens')->nullable();
            $table->unsignedInteger('cache_creation_tokens')->nullable();

            // Request parameters
            $table->decimal('temperature', 3, 2)->nullable();
            $table->unsignedInteger('max_tokens')->nullable();
            $table->decimal('top_p', 3, 2)->nullable();

            // Response
            $table->json('finish_reasons')->nullable();

            // Status
            $table->string('status_code', 20);
            $table->text('status_description')->nullable();

            // Full attributes (all other attributes as JSON)
            $table->json('attributes')->nullable();

            // Events (for special occurrences during span)
            $table->json('events')->nullable();

            // Links (to other spans)
            $table->json('links')->nullable();

            $table->timestamp('created_at');

            // Indexes for performance (prefixed with 'spans_' to avoid SQLite conflicts)
            $table->index('trace_id', 'idx_spans_trace_id');
            $table->index('span_id', 'idx_spans_span_id');
            $table->index('parent_span_id', 'idx_spans_parent');
            $table->index(['name' => 255], 'idx_spans_name');
            $table->index(['operation_name', 'provider_name'], 'idx_spans_operation');
            $table->index('request_model', 'idx_spans_model');
            $table->index(['input_tokens', 'output_tokens'], 'idx_spans_tokens');
            $table->index('created_at', 'idx_spans_created');

            // Foreign key constraint
            $table->foreign('trace_id', 'fk_spans_trace')
                ->references('trace_id')
                ->on('mindwave_traces')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mindwave_spans');
    }
};
