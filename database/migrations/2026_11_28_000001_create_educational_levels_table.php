<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedInteger('display_order');
            $table->boolean('is_active')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'name']);
            $table->unique(['school_id', 'code']);
            $table->unique(['school_id', 'display_order']);
            $table->unique(['school_id', 'id']);
            $table->index(['school_id', 'is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educational_levels');
    }
};
