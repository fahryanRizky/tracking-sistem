<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status_previous');
            $table->text('rejection_reason')->nullable();
            $table->json('data_snapshot');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['claim_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_revisions');
    }
};
