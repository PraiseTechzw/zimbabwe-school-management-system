<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_identifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('type');
            $table->string('value');
            $table->string('normalized_value');
            $table->string('source')->nullable();
            $table->string('verification_status')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['type', 'normalized_value']);
            $table->index(['school_id', 'type', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_identifiers');
    }
};
