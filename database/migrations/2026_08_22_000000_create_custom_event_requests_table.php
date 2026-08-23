<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('custom_event_requests')) {
            return;
        }

        Schema::create('custom_event_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('coordinator_id');
            $table->string('event_type', 100);
            $table->date('event_date');
            $table->string('venue_preference', 255)->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->string('theme', 120)->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->text('required_services')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('additional_notes')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();

            $table->index('event_id');
            $table->index('client_id');
            $table->index('coordinator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_event_requests');
    }
};