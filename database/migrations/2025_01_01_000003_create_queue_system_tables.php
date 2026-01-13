<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Lantai 1, Lantai 2
            $table->integer('level')->default(1);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // CS, Teller
            $table->string('code', 10); // A, B, C
            $table->integer('start_number')->default(1);
            $table->integer('last_number')->default(0); // For quick generation
            $table->timestamps();
        });

        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained()->cascadeOnDelete();
            // A counter might support multiple services, but simple design is usually 1 primary service or any. 
            // For simplicity in this demo: A counter can serve any service on that floor, but we track active status.
            $table->string('name'); // Loket 1, Loket 2
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('floor_id')->constrained()->cascadeOnDelete(); // Denormalized for query monitoring
            $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('number');
            $table->string('full_number'); // A-001
            $table->enum('status', ['waiting', 'called', 'served', 'skipped', 'cancelled'])->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
        Schema::dropIfExists('counters');
        Schema::dropIfExists('services');
        Schema::dropIfExists('floors');
    }
};
