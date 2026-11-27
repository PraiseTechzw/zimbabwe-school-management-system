<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_statuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('status');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'status', 'valid_from']);
            $table->index(['school_id', 'valid_to', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_statuses');
    }
};
