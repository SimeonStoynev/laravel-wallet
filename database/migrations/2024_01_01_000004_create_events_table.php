<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type');
            $table->unsignedBigInteger('aggregate_id');
            $table->string('event_type');
            $table->json('event_data');
            $table->json('metadata')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            // Indexes for performance optimization
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index(['event_type']);
            $table->index(['occurred_at']);
            $table->index(['aggregate_type', 'aggregate_id', 'version']);

            // Composite index for event sourcing queries
            $table->index(['aggregate_type', 'aggregate_id', 'occurred_at'], 'events_aggregate_timeline_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
