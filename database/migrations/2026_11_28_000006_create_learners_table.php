<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('learner_reference', 32);
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('status', 32)->default('registered');
            $table->foreignId('class_offering_id')->nullable();
            $table->timestamps();

            $table->unique('learner_reference', 'learners_reference_unique');
            $table->unique(['school_id', 'id'], 'learners_school_id_id_unique');
            $table->index(['school_id', 'class_offering_id'], 'learners_school_offering_index');

            // The offering inherits school ownership from the parent. Restrict
            // deletion so a removed offering cannot null school_id through the
            // composite key or silently delete a learner record.
            $table->foreign(['school_id', 'class_offering_id'])
                ->references(['school_id', 'id'])
                ->on('class_offerings')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learners');
    }
};
