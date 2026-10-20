<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Proyek menjadi induk klaim yang diajukan oleh vendor.
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->restrictOnDelete();
            $table->string('claim_number')->unique();
            $table->enum('status', ['submitted', 'reviewed', 'approved', 'rejected'])->default('submitted');
            $table->decimal('total_claim_amount', 15, 2)->default(0);
            $table->decimal('total_actual_amount', 15, 2)->default(0);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->string('unit');
            $table->decimal('claimed_quantity', 10, 2);
            $table->decimal('claimed_unit_price', 15, 2);
            $table->decimal('actual_quantity', 10, 2)->nullable();
            $table->decimal('actual_unit_price', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('action');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('claim_items');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('projects');
    }
};
