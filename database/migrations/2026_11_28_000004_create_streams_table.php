<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_definition_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('display_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite FK prevents associating a stream with another school's class.
            $table->foreign(['school_id', 'class_definition_id'])
                ->references(['school_id', 'id'])
                ->on('class_definitions')
                ->cascadeOnDelete();

            $table->unique(['school_id', 'class_definition_id', 'name']);
            $table->unique(['school_id', 'class_definition_id', 'code']);
            $table->unique(['school_id', 'class_definition_id', 'display_order']);
            $table->unique(['school_id', 'id']);
            $table->index(
                ['school_id', 'class_definition_id', 'is_active', 'display_order'],
                'streams_school_class_active_order_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
