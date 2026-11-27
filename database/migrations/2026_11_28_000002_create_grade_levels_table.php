<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('educational_level_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('display_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite FK prevents associating a grade with another school's level.
            $table->foreign(['school_id', 'educational_level_id'])
                ->references(['school_id', 'id'])
                ->on('educational_levels')
                ->cascadeOnDelete();

            $table->unique(['school_id', 'name']);
            $table->unique(['school_id', 'code']);
            $table->unique(['school_id', 'educational_level_id', 'display_order']);
            $table->unique(['school_id', 'id']);
            $table->index(['school_id', 'educational_level_id', 'is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
