<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('grade_level_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('display_order');
            $table->boolean('is_active')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamps();

            // Composite FK prevents associating a class with another school's grade.
            $table->foreign(['school_id', 'grade_level_id'])
                ->references(['school_id', 'id'])
                ->on('grade_levels')
                ->cascadeOnDelete();

            $table->unique(['school_id', 'grade_level_id', 'name']);
            $table->unique(['school_id', 'grade_level_id', 'code']);
            $table->unique(['school_id', 'grade_level_id', 'display_order']);
            $table->unique(['school_id', 'id']);
            $table->index(
                ['school_id', 'grade_level_id', 'is_active', 'display_order'],
                'class_defs_school_grade_active_order_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_definitions');
    }
};
