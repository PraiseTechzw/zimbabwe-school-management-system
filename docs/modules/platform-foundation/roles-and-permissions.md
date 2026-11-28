# Roles and permissions foundation — implementation record

**Status:** IMPLEMENTED, pending review. **Issue:** `P1-I04` (#37). **Evidence class:** PROPOSED DESIGN / engineering baseline.

This slice adds the server-side authorization foundation. It describes authority relationships without hardcoding school workflows and gives later slices a reusable way to enforce least privilege. It does not retrofit every existing endpoint.

## Scope

Included:

- Config-driven permission and role catalogues.
- School-scoped role assignments stored as `role_assignments`.
- A reusable permission resolver and HTTP middleware.
- Permission-protected role-assignment list, create and delete endpoints.
- A trusted CLI path for the first assignment in a school.
- Tests for least privilege, non-escalation and cross-school isolation.

Not included:

- Admissions, learner, finance, welfare, staff or other school workflows.
- Changes to the academic calendar or school-context endpoints.
- Authentication changes, MFA, session policy or password policy.
- Separation-of-duties workflows, time-bounded delegations, acting-role records or periodic access review.
- Audit events for permission changes, role-management UI or dashboards.
- Global/platform roles or cross-school administration.

## Catalogues

`config/authorization.php` defines:

- `permissions`: named action boundaries such as `academic_calendar.manage` and `roles.manage`.
- `roles`: role slugs and their permission lists. A role may use `*` to receive every configured permission; only the bootstrap `school_admin` role does this.
- `assignment_sources`: allow-listed origins for an assignment.

Adding a role or permission is a configuration change. The database stores only a role slug on each assignment, not a copy of the permission list, so role contents are resolved at request time.

## Data model

`role_assignments` is owned by the authorization domain.

| Column | Purpose |
| --- | --- |
| `id` | Assignment identifier. |
| `school_id` | Server-resolved school; foreign key cascades if a school is removed. |
| `user_id` | The user receiving the role; foreign key cascades if a user is removed. |
| `role` | Configured role slug, validated before writes. |
| `source` | `domain`, `cli` or `import`. |
| `created_at` | Server persistence time. |

There is no `user_permissions` table: users never receive permissions directly. The unique key `(school_id, user_id, role)` prevents duplicate assignments.

## Permission resolution

`SchoolPermissions` resolves a permission only when all of these are true:

1. The permission is present in the configured catalogue.
2. The user is still a member of the school.
3. An active role assignment in that school grants the permission.

A stale assignment therefore cannot grant access after school membership is removed. Unknown permissions and unknown roles fail closed. A role containing `*` grants only currently configured permissions; removing a permission from the catalogue removes it everywhere.

## Enforcement

`EnsurePermission` is a reusable middleware that reads the actor from the authenticated user and the school from `SchoolContext`, never from client input. It aborts with 401 when unauthenticated, 403 when there is no school context or permission, and otherwise calls the next handler.

Routes opt in explicitly, for example:

```php
Route::post('/role-assignments', [RoleAssignmentController::class, 'store'])
    ->middleware(EnsurePermission::class.':roles.manage');
```

The domain actions repeat the check so direct server-side callers cannot bypass it. `AssignSchoolRole` also refuses to grant a role whose permissions are not already held by the actor. This prevents a manager from escalating privileges through role assignment.

Academic Structure (#59) opts in with `academic_structure.view` for reads and `academic_structure.manage` for writes. After B-04, calendar writes and the calendar management screen also opt in with `academic_calendar.manage`. Each future domain must opt in to the relevant permission before its endpoints are considered authorized. Calendar reads, school-context reads and school settings remain membership-only; see the B-04 audit for unresolved decisions.

## HTTP surface

| Method | Path | Required permission |
| --- | --- | --- |
| `GET` | `/api/role-assignments` | `roles.view` |
| `POST` | `/api/role-assignments` | `roles.manage` |
| `DELETE` | `/api/role-assignments/{roleAssignment}` | `roles.manage` |

All routes use `web`, `auth` and `school.context`. Client-supplied `school_id` is rejected. Assignment output contains identifiers, role and source; it does not expose names or email addresses. JSON responses use `Cache-Control: no-store` and vary on school selection inputs.

Create input accepts `user_id` and `role` only; the source is set server-side to `domain`. The target must already belong to the server-resolved school. Duplicate assignments and unknown roles return 422.

## Bootstrap path

The first assignment in a school is a trusted operator action:

```bash
php artisan roles:assign --school=1 --user=2 --role=school_admin
```

The command validates school membership and the configured role, writes with source `cli`, and is intentionally not exposed over HTTP. Later assignments can be made through the permission-protected endpoints.

## Tenant isolation and privacy

- School context comes only from `ResolveSchoolContext`.
- The actor comes only from the authenticated user.
- List, assign and remove operations are constrained to the current school.
- A cross-school assignment target is rejected; a cross-school assignment ID is not found.
- Role assignments contain identifiers only.
- No permission is stored directly on a user.

## Known limitations and owner decisions

- Existing endpoints remain unauthorized until their owning slices opt into the middleware.
- `school_admin` is a broad bootstrap role; it should be assigned sparingly. Real role taxonomy and delegation limits need Product Owner confirmation.
- Configuration changes take effect immediately for all users holding the affected role. There is no versioning or approval workflow for the catalogue.
- No MFA, periodic review, separation-of-duties or time-bounded delegation is implemented.
- Permission changes are not written to the audit trail; that integration belongs to a later slice.
- Direct database writes can bypass application checks; deployment ownership must restrict privileged database access.
- No role-management UI is included; permissions must exist before any UI is added.
