<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_setting_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_setting_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('values');
            $table->string('source');
            $table->date('effective_from');
            $table->timestamps();

            $table->unique(['school_id', 'version']);
            $table->index(['school_setting_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_setting_revisions');
    }
};
