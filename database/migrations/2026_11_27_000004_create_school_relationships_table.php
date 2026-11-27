<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('related_school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('relationship_type');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('evidence_reference')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'related_school_id', 'relationship_type', 'valid_from']);
            $table->index(['related_school_id', 'relationship_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_relationships');
    }
};
