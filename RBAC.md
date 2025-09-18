# RBAC Design Notes

Role-Based Access Control (RBAC) design for Unitarius framework.

---

## Core Concepts

- **Roles** – groups of permissions (e.g. `admin`, `editor`, `viewer`).
- **Permissions** – fine-grained rights, namespaced per module (e.g. `hr.user.read`, `finance.invoice.update`).
- **Members ↔ Roles** – many-to-many relation between users and roles.
- **Role ↔ Permissions** – many-to-many relation between roles and permissions.

---

## Database Schema (simplified)

- `roles(id, slug, name)`
- `permissions(id, code, description)`
- `role_permissions(role_id, permission_id)`
- `members(id, person_id, username, email, password_hash, ...)`
- `member_roles(member_id, role_id)`

Optional tables:
- `permission_implications(src_permission_id, implies_permission_id)` – handle "write ⇒ read".
- `auth_providers` – for SSO mapping.
- `sessions` – active sessions if not JWT.

---

## RBAC Flow

1. **Login (local or SSO)**
   - User authenticates.
   - Server loads roles + permissions from DB.
   - Store in `$_SESSION['auth']['permissions']` (or JWT claim).

2. **Middleware Check**
   - Example: `requirePermission('members.user.update')`
   - If not in permission set → `403 Forbidden`.
   - Multiple permissions:
     - `requireAny(['a','b'])` – allow if **any** present.
     - `requireAll(['a','b'])` – require **all**.

3. **Role Changes**
   - Use `permissions_version` or `perm_hash` to detect changes.
   - Force re-sync or logout when changed.

---

## Permission Naming

- Prefix by module: `hr.*`, `crm.*`, `finance.*`.
- Convention: `resource.action` (`user.read`, `user.write`, `user.delete`).
- Implied: `*.write` implies `*.read` (handled in resolver).
- Optional wildcards: `hr.user.*` covers all.

---

## Middleware Helpers

```php
function hasPermission(string $code): bool;
function hasAny(array $codes): bool;
function hasAll(array $codes): bool;

function requirePermission(string $code): void;
function requireAny(array $codes): void;
function requireAll(array $codes): void;
```

---

## Example Usage

### Route → Middleware → Controller

```php
$router->post('/members/{id}/update', function () {
    requirePermission('members.user.update');
    return (new MemberController)->update();
});
```

### Multiple Permissions

```php
$router->post('/members/{id}/delete', function () {
    requireAll(['members.user.delete', 'audit.write']);
    return (new MemberController)->delete();
});
```

### Policy Function (RBAC + ABAC)

```php
function canEditPerson(string $personId): bool {
    if (hasPermission('persons.manage')) return true;
    if (hasPermission('persons.update_own')) {
        return in_array($personId, $_SESSION['auth']['person_ids'] ?? [], true);
    }
    return false;
}
```

---

## GUI Integration

- UI should hide/disable elements based on permissions.
- Example: if user has only `read` → show readonly input, hide Save button.
- Never trust UI: backend must always enforce RBAC.

### SSR Example

```php
<?php if (can('members.user.update')): ?>
  <input type="text" name="email" value="<?= e($user['email']) ?>">
  <button type="submit">Save</button>
<?php else: ?>
  <input type="text" value="<?= e($user['email']) ?>" readonly>
<?php endif; ?>
```

### SPA Example

```js
const caps = new Set(me.permissions);
const can = (p) => caps.has(p) || caps.has('members.user.*');

if (can('members.user.update')) renderEditable();
else if (can('members.user.read')) renderReadOnly();
```

---

## Seed Roles

- **Admin** – full access to all modules.
- **Editor** – can create/update, cannot manage roles.
- **Viewer** – read-only access.

---

## Notes

- RBAC is the base; ABAC rules can be layered later.
- Use caching for permissions in session/JWT.
- Always log important actions for auditing.
