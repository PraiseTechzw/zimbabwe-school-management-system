<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_offerings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_definition_id');
            $table->foreignId('academic_year_id');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamps();

            // Both composite FKs inherit school ownership from the parents.
            $table->foreign(['school_id', 'class_definition_id'])
                ->references(['school_id', 'id'])
                ->on('class_definitions')
                ->cascadeOnDelete();

            $table->foreign(['school_id', 'academic_year_id'])
                ->references(['school_id', 'id'])
                ->on('academic_years')
                ->cascadeOnDelete();

            $table->unique(
                ['school_id', 'academic_year_id', 'class_definition_id'],
                'class_offerings_school_year_class_unique',
            );
            $table->unique(
                ['school_id', 'academic_year_id', 'name'],
                'class_offerings_school_year_name_unique',
            );
            $table->unique(['school_id', 'id']);
            $table->index(['school_id', 'academic_year_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_offerings');
    }
};
