<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')
                ->nullable()
                ->constrained('schools')
                ->restrictOnDelete();
            $table->string('actor_type', 50)->nullable();
            $table->string('actor_id', 255)->nullable();
            $table->string('action', 150);
            $table->string('category', 100)->nullable();
            $table->string('resource_type', 150);
            $table->string('resource_id', 255);
            $table->timestamp('occurred_at');
            $table->string('correlation_id', 100)->nullable();
            $table->string('source', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at');

            $table->index(['school_id', 'occurred_at']);
            $table->index(['school_id', 'action']);
            $table->index(['school_id', 'resource_type', 'resource_id']);
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
