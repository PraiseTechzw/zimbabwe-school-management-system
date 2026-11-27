# Academic Structure API

Merged in PR #59. These endpoints configure a school's own educational levels, grade levels, class definitions, streams and class offerings. They are mounted by `AcademicStructureServiceProvider` under `/api` with `web`, `auth` and `school.context` middleware. Every route also applies `EnsurePermission`: `academic_structure.view` for reads and `academic_structure.manage` for writes.

Tenant selection and response conventions:

- A user with one school needs no selection input; a user with multiple schools must send `X-School-Id`.
- `school_id` in a query string or request body is rejected with `422` and is never accepted as proof of access.
- An unauthorised `X-School-Id` returns `403`; ambiguous selection returns `400`.
- Route parents are resolved with the current school scope. A foreign parent returns `404`, not a validation hint about another tenant.
- Writes resolve the school server-side. Cross-school body references return a generic `422`; rejected writes produce no structure audit event.
- All responses are JSON with `Cache-Control: no-store` and `Vary: X-School-Id, school_id`.
- There is no delete or archive endpoint. Deletion is only a database cascade and remains an unresolved product decision.

## Common fields

| Field | Applies to | Rules |
| --- | --- | --- |
| `name` | all created records | required string, max 255, not blank |
| `code` | levels, grades, classes, streams | nullable string, max 64, not blank when supplied |
| `display_order` | levels, grades, classes, streams | required integer, `1..max_display_order` from `config/academic-structure.php` |
| `is_active` | all records | optional boolean |
| `configuration` | levels, classes, offerings | optional nullable array; no size, depth or key allowlist is enforced |
| `school_id` | every write and offerings list | prohibited |
| `class_definition_id` | offering create/update | required on create; optional on update; integer, min 1 |
| `academic_year_id` | offering create/update and offerings list | required on create; optional on update/list; integer, min 1 |

Nested create endpoints take the parent from the route. `POST` bodies must not supply `educational_level_id`, `grade_level_id` or `class_definition_id` for those nested resources. Update endpoints may supply the relevant parent ID to move a record, but only within the current school.

Successful creates return `201`; reads and updates return `200`. Validation failures use Laravel's standard JSON shape:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "school_id": ["The school id field is prohibited."],
    "academic_year_id": ["The selected academic year id is invalid."]
  }
}
```

## Response objects

```json
{
  "id": 1,
  "school_id": 7,
  "name": "Example name",
  "code": null,
  "display_order": 10,
  "is_active": true,
  "configuration": null
}
```

Levels, classes and offerings include `configuration` when defined. Grades and streams omit it. Classes add `grade_level_id`; streams add `class_definition_id`; offerings add `class_definition_id` and `academic_year_id` and omit `code` and `display_order`.

## Educational levels

| Method | Path | Permission | Response |
| --- | --- | --- | --- |
| `GET` | `/api/academic-structure/levels` | view | `{"school_id":7,"educational_levels":[...]}` |
| `POST` | `/api/academic-structure/levels` | manage | `{"educational_level":{...}}`, `201` |
| `PUT\|PATCH` | `/api/academic-structure/levels/{educationalLevel}` | manage | `{"educational_level":{...}}` |

List results are ordered by `display_order`, then `id`.

## Grade levels

| Method | Path | Permission | Response |
| --- | --- | --- | --- |
| `GET` | `/api/academic-structure/grade-levels` | view | `{"school_id":7,"grade_levels":[...]}` |
| `GET` | `/api/academic-structure/levels/{educationalLevel}/grade-levels` | view | `{"school_id":7,"educational_level_id":1,"grade_levels":[...]}` |
| `POST` | `/api/academic-structure/levels/{educationalLevel}/grade-levels` | manage | `{"grade_level":{...}}`, `201` |
| `PUT\|PATCH` | `/api/academic-structure/grade-levels/{gradeLevel}` | manage | `{"grade_level":{...}}` |

Updates may supply `educational_level_id` to move the grade within the current school.

## Classes

| Method | Path | Permission | Response |
| --- | --- | --- | --- |
| `GET` | `/api/academic-structure/grade-levels/{gradeLevel}/classes` | view | `{"school_id":7,"grade_level_id":2,"classes":[...]}` |
| `POST` | `/api/academic-structure/grade-levels/{gradeLevel}/classes` | manage | `{"class":{...}}`, `201` |
| `PUT\|PATCH` | `/api/academic-structure/classes/{classDefinition}` | manage | `{"class":{...}}` |

Updates may supply `grade_level_id` to move the class within the current school.

## Streams

| Method | Path | Permission | Response |
| --- | --- | --- | --- |
| `GET` | `/api/academic-structure/classes/{classDefinition}/streams` | view | `{"school_id":7,"class_definition_id":3,"streams":[...]}` |
| `POST` | `/api/academic-structure/classes/{classDefinition}/streams` | manage | `{"stream":{...}}`, `201` |
| `PUT\|PATCH` | `/api/academic-structure/streams/{stream}` | manage | `{"stream":{...}}` |

Updates may supply `class_definition_id` to move the stream within the current school. Stream meaning remains school-defined and unresolved in STR-21.

## Class offerings

| Method | Path | Permission | Response |
| --- | --- | --- | --- |
| `GET` | `/api/academic-structure/class-offerings` | view | `{"school_id":7,"academic_year_id":4\|null,"class_offerings":[...]}` |
| `POST` | `/api/academic-structure/class-offerings` | manage | `{"class_offering":{...}}`, `201` |
| `PUT\|PATCH` | `/api/academic-structure/class-offerings/{classOffering}` | manage | `{"class_offering":{...}}` |

The optional `academic_year_id` query parameter filters the list; when omitted, the response echoes `null`. A foreign academic year returns `404`, while a foreign `class_definition_id` on a write is rejected without revealing another school's data.

## Verification

The endpoint contract was read from `routes/academic-structure.php` and `AcademicStructureController.php`. Automated coverage is in `tests/Feature/Domains/AcademicStructure/AcademicStructureTest.php`, `AcademicStructureIsolationTest.php`, `AcademicStructurePermissionTest.php`, `AcademicStructureAuditTest.php` and `AcademicStructureSchemaTest.php`. See [the module record](../modules/academic-structure.md) for verified command results and limitations.
