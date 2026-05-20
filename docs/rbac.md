# RBAC Middleware (api/rbac.php)

Purpose: centralize role and ownership checks for API endpoints.

Core functions:
- `authorizeRole($roleOrArray)` — requires login and checks user role.
- `authorizeOwnerOrAdmin($table, $id, $ownerCol='user_id')` — allows admin or owner of a DB row.
- `authorizeEmployerOrAdminForApplication($applicationId)` — allows admin or the employer that owns the job for that application.
- `authorizeRoleOrAdmin($role)` — allows admin or a specific role.

Examples:

- Protect a route so only employers can post jobs:

  $user = authorizeRole('employer');

- Allow job owner or admin to edit/delete a job:

  $user = authorizeOwnerOrAdmin('jobs', $jobId, 'employer_id');

- Allow job owner (by application) or admin to update application status:

  $user = authorizeEmployerOrAdminForApplication($applicationId);

Integration: include the file from endpoints in `api/` using:

    require_once 'rbac.php';

Notes: these helpers use existing `requireLogin()` and `jsonResponse()` from `config/session.php`.
