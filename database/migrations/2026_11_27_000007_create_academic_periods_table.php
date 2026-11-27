<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('sequence');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status');
            $table->string('counting_basis')->nullable();
            $table->string('source')->nullable();
            $table->string('source_reference')->nullable();
            $table->date('source_published_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
            $table->unique(['academic_year_id', 'sequence']);
            $table->index(['school_id', 'academic_year_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
