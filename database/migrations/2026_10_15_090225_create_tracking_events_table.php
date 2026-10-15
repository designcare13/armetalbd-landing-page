<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 60);
            $table->string('event_id', 100);
            $table->string('reference')->nullable(); // for example the order number; never personal data
            $table->decimal('value', 12, 2)->nullable();
            $table->string('status', 20)->default('pending'); // pending, sent, failed
            $table->unsignedInteger('events_received')->nullable();
            $table->string('fbtrace_id', 100)->nullable();
            $table->text('error')->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestamps();

            $table->unique(['event_name', 'event_id']); // an event is sent to Meta only once
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
