<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\AcademicStructure\Models\ClassDefinition;
use App\Domains\AcademicStructure\Models\Stream;
use App\Domains\School\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stream>
 */
class StreamFactory extends Factory
{
    protected $model = Stream::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'class_definition_id' => ClassDefinition::factory(),
            'name' => 'Stream '.fake()->unique()->numberBetween(1, 5000),
            'code' => null,
            'display_order' => fake()->unique()->numberBetween(1, 5000),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Stream $stream): void {
            if ($stream->classDefinition !== null) {
                $stream->school_id = $stream->classDefinition->school_id;
            }
        });
    }

    public function inClass(ClassDefinition $class): static
    {
        return $this->state(fn (): array => [
            'school_id' => $class->school_id,
            'class_definition_id' => $class->id,
        ]);
    }
}
