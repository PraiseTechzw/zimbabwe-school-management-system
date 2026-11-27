<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status');
            $table->string('source')->nullable();
            $table->string('source_reference')->nullable();
            $table->date('source_published_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->index(['school_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
