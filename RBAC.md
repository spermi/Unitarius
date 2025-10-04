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

---
   roles
CREATE TABLE IF NOT EXISTS roles (
  id BIGSERIAL PRIMARY KEY,
  name  VARCHAR(100) UNIQUE NOT NULL,   -- e.g. 'admin'
  label VARCHAR(150) NOT NULL,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- permissions
CREATE TABLE IF NOT EXISTS permissions (
  id BIGSERIAL PRIMARY KEY,
  name  VARCHAR(150) UNIQUE NOT NULL,   -- e.g. 'users.view', 'rbac.manage'
  label VARCHAR(200) NOT NULL,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- user <-> role
CREATE TABLE IF NOT EXISTS user_roles (
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  PRIMARY KEY (user_id, role_id)
);

-- role <-> permission
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  permission_id BIGINT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
  PRIMARY KEY (role_id, permission_id)
);


INSERT 


-- seed role
INSERT INTO roles (name, label)
VALUES ('admin','Admin')
ON CONFLICT (name) DO NOTHING;

-- seed permissions
INSERT INTO permissions (name, label) VALUES
  ('users.view',  'List users'),
  ('rbac.manage', 'Manage RBAC')
ON CONFLICT (name) DO NOTHING;

-- grant all current permissions to admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'admin'
ON CONFLICT DO NOTHING;


add permission 
-- replace this with your real email
WITH me AS (
  SELECT id AS user_id FROM users WHERE email = 'YOUR.EMAIL@EXAMPLE.COM' LIMIT 1
),
admin_role AS (
  SELECT id AS role_id FROM roles WHERE name = 'admin' LIMIT 1
)
INSERT INTO user_roles (user_id, role_id)
SELECT me.user_id, admin_role.role_id FROM me, admin_role
ON CONFLICT DO NOTHING;
-


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



Hogyan működik nálunk az RBAC és a CRUD?

Adatmodell: roles, permissions, user_roles, role_permissions.

Betöltés: belépéskor load_permissions_for_user() beolvassa a user összes engedélyét, cache: $_SESSION['perm_cache'].

Ellenőrzés:

Route-on: new RequirePermission('...') → ha nincs, 403.

Menü: MenuLoader → ha nincs, elrejtjük.

CRUD (RBAC UI):

Read: most kész — listák a szerepekről, engedélyekről, hozzárendelésekről.

Create/Update/Delete (következő lépés):

roles: létrehozás, átnevezés, törlés (törlés előtt ütközés-check: van-e hozzárendelés).

permissions: ugyanez.

assignments: user↔role és role↔permission hozzárendelés/levétel.

Védelem: minden művelet RequirePermission('rbac.manage') alatt; űrlapoknál majd CSRF, szerver oldali validáció.