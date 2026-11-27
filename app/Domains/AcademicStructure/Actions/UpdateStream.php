<?php

declare(strict_types=1);

namespace App\Domains\AcademicStructure\Actions;

use App\Domains\AcademicStructure\Concerns\GuardsAcademicStructure;
use App\Domains\AcademicStructure\Concerns\RecordsAcademicStructureAudit;
use App\Domains\AcademicStructure\Models\Stream;
use App\Domains\AcademicStructure\Support\AcademicStructureRules;
use App\Domains\School\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateStream
{
    use GuardsAcademicStructure;
    use RecordsAcademicStructureAudit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(School $school, Stream $stream, array $data): Stream
    {
        $validated = Validator::make(array_merge([
            'class_definition_id' => $stream->class_definition_id,
            'name' => $stream->name,
            'code' => $stream->code,
            'display_order' => $stream->display_order,
            'is_active' => $stream->is_active,
        ], $data), [
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
        $active = $validated['is_active'] === null ? (bool) $stream->is_active : (bool) $validated['is_active'];

        return DB::transaction(function () use ($school, $stream, $validated, $name, $code, $order, $active): Stream {
            $this->lockStructureForSchool($school);
            $this->assertOwnedBySchool($school, $stream);
            $class = $this->resolveClassDefinition($school, (int) $validated['class_definition_id']);

            $this->assertUniqueLabel(
                Stream::class,
                'name',
                $name,
                ['school_id' => $school->id, 'class_definition_id' => $class->id],
                'name',
                'A stream with this name already exists in the selected class.',
                (int) $stream->id,
            );

            if ($code !== null) {
                $this->assertUniqueLabel(
                    Stream::class,
                    'code',
                    $code,
                    ['school_id' => $school->id, 'class_definition_id' => $class->id],
                    'code',
                    'This code is already used in the selected class.',
                    (int) $stream->id,
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
                (int) $stream->id,
            );

            $before = $this->streamAuditSnapshot($stream);

            $stream->fill([
                'name' => $name,
                'code' => $code,
                'display_order' => $order,
                'is_active' => $active,
            ]);
            $stream->class_definition_id = $class->id;
            $stream->save();

            $this->recordStructureAudit(
                $school,
                'stream.updated',
                'stream',
                (string) $stream->id,
                $before,
                $this->streamAuditSnapshot($stream),
            );

            return $stream;
        });
    }
}
