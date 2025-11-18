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
        Schema::create('mindwave_traces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('trace_id', 32)->unique();
            $table->string('service_name');
            $table->unsignedBigInteger('start_time')->comment('Start time in nanoseconds');
            $table->unsignedBigInteger('end_time')->nullable()->comment('End time in nanoseconds');
            $table->unsignedBigInteger('duration')->nullable()->comment('Duration in nanoseconds');
            $table->string('status', 20)->default('unset')->comment('ok, error, unset');
            $table->char('root_span_id', 16)->nullable();
            $table->unsignedInteger('total_spans')->default(0);
            $table->unsignedInteger('total_input_tokens')->default(0);
            $table->unsignedInteger('total_output_tokens')->default(0);
            $table->decimal('estimated_cost', 10, 6)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('trace_id', 'idx_trace_id');
            $table->index(['service_name', 'created_at'], 'idx_service_created');
            $table->index('duration', 'idx_duration');
            $table->index('estimated_cost', 'idx_cost');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mindwave_traces');
    }
};
