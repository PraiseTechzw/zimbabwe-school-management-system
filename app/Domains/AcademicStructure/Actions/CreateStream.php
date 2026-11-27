<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Actions;

use App\Domains\AcademicStructure\Concerns\GuardsAcademicStructure;
use App\Domains\AcademicStructure\Models\Stream;
use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class CreateStream
{
    use GuardsAcademicStructure;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, array $data): Stream
    {
        $validated = Validator::make($data, [
            'class_definition_id' => ['required', 'integer', 'min:1'],
            'name' => AcademicStructureRules::name(),
            'code' => AcademicStructureRules::code(),
            'display_order' => AcademicStructureRules::displayOrder(),
            'is_active' => array_merge(['nullable'], AcademicStructureRules::active()),
        ])->validate();

        $name = trim((string) $validated['name']);
        $code = isset($validated['code']) ? trim((string) $validated['code']) : null;
        $code = $code === '' ? null : $code;
        $order = (int) $validated['display_order'];
        $active = (bool) ($validated['is_active'] ?? config('academic-structure.default_is_active', true));

        return DB::transaction(function () use ($school, $validated, $name, $code, $order, $active): Stream {
            $this->lockStructureForSchool($school);
            $class = $this->resolveClassDefinition($school, (int) $validated['class_definition_id']);

            $this->assertUniqueLabel(
                Stream::class,
                'name',
                $name,
                ['school_id' => $school->id, 'class_definition_id' => $class->id],
                'name',
                'A stream with this name already exists in the selected class.',
            );

            if ($code !== null) {
                $this->assertUniqueLabel(
                    Stream::class,
                    'code',
                    $code,
                    ['school_id' => $school->id, 'class_definition_id' => $class->id],
                    'code',
                    'This code is already used in the selected class.',
                );
            }

            $this->assertNoDuplicate(
                Stream::class,
                [
                    'school_id' => $school->id,
                    'class_definition_id' => $class->id,
                    'display_order' => $order,
                ],
                'display_order',
                'This display order is already used in the selected class.',
            );

            $stream = new Stream;
            $stream->fill([
                'name' => $name,
                'code' => $code,
                'display_order' => $order,
                'is_active' => $active,
            ]);
            $stream->school_id = $school->id;
            $stream->class_definition_id = $class->id;
            $stream->save();

            return $stream;
        });
    }
}
