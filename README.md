# Unitarius PHP Microframework

Minimal PHP microframework built from scratch for learning and internal use.

---

## Development Environment 

- Windows + VSCode
- WampServer (Apache + PHP 8.4)
- PostgreSQL
- Composer 2.8.11
- AdminLTE V4

---

## Project Structure (with descriptions)

```
unitarius/
├─ app/                                   # Application layer (controllers, views, per-app modules)
│  ├─ Apps/                               # Modular "mini-apps" live here; each app is self-contained
│  │  ├─ People/                          # People management mini-app
│  │  │  ├─ Controllers/                  # Business logic layer (CRUD for people)
│  │  │  │  └── PeopleController.php
│  │  │  ├─ Views/                        # Views rendered through global layout
│  │  │  │  ├── list.php                  # People list table
│  │  │  │  └── form.php                  # Create/Edit person form
│  │  │  ├── manifest.php                 # Menu manifest for People (used in sidebar)
│  │  │  └── routes.php                   # Local routes (mounted by prefix)
│  │  ├─ Rbac/                            # RBAC management app (roles, permissions, assignments)
│  │  │  ├─ Controllers/
│  │  │  │  └── RbacController.php        # Handles RBAC CRUD + assignment guards
│  │  │  ├─ Views/
│  │  │  │  ├── index.php                 # RBAC dashboard
│  │  │  │  ├── roles.php                 # Roles list
│  │  │  │  ├── role_form.php             # Create/Edit role form
│  │  │  │  ├── permissions.php           # Permissions list
│  │  │  │  ├── perm_form.php             # Create/Edit permission form
│  │  │  │  └── assignments.php           # User↔Role and Role↔Permission mappings
│  │  │  ├── manifest.php                 # Menu manifest for RBAC (used in sidebar)
│  │  │  └── routes.php                   # RBAC routes (protected by RequirePermission)
│  │  ├─ Users/                           # User management app (separate from RBAC)
│  │  │  ├─ Controllers/
│  │  │  │  └── UserController.php        # Handles user listing and CRUD
│  │  │  ├─ Views/
│  │  │  │  ├── list.php                  # User list view (email, status, etc.)
│  │  │  │  └── user_form.php             # Edit/Create user form
│  │  │  ├── manifest.php                 # Menu manifest for RBAC (used in sidebar)
│  │  │  └── routes.php                   # User routes (uses RBAC middleware)
│  │  └─ (more apps as needed)/
│  ├─ Controllers/
│  │  ├── AuthController.php              # Handles login, logout, Google OAuth
│  │  └── DashboardController.php         # Main dashboard (authenticated landing page)
│  ├─ Models/                             # (Reserved for future ORM-style models)
│  └─ Views/
│      ├─ dashboard/
│      │  └── index.php                   # Dashboard view
│      ├─ errors/
│      │  ├── 401.php                     # Unauthorized
│      │  ├── 403.php                     # Forbidden
│      │  ├── 404.php                     # Not Found
│      │  ├── 419.php                     # CSRF token expired
│      │  └── 500.php                     # Server error
│      ├─ layout.php                      # Global AdminLTE layout (header, sidebar, footer)
│      ├─ login.php                       # Standalone login page (not using main layout)
│      └─ partials/
│          ├── breadcrumbs.php             # Optional breadcrumb nav
│          ├── flash.php                   # Flash message renderer
│          ├── navbar.php                  # Top navigation bar
│          └── sidebar.php                 # Dynamic sidebar (built by MenuLoader)
├─ config/
│  └── menu.core.php                      # Static core menu entries (e.g., Dashboard)
├─ public/
│  ├─ assets/
│  │  ├─ adminlte/                        # AdminLTE v4 static assets (local copy)
│  │  │  ├─ css/                          # AdminLTE CSS files
│  │  │  ├─ js/                           # AdminLTE JS files
│  │  │  └─ img/                          # Template images (logos, avatars, backgrounds)
│  │  ├─ css/
│  │  │  ├── custom.css                   # Project custom overrides (e.g., dark mode fixes)
│  │  │  ├── choices.css                  # Choices.js (dropdown plugin)
│  │  │  └── choices.min.css
│  │  └─ js/
│  │      ├── choices.js                  # Choices.js (local copy)
│  │      └── choices.min.js
│  └─ index.php                           # Front controller (bootstraps kernel + router)
├─ src/
│  ├─ Core/
│  │  ├── DB.php                          # PostgreSQL PDO wrapper
│  │  ├── ErrorHandler.php                # Exception and error logging
│  │  ├── Helpers.php                     # Global helpers (base_url, csrf_field, etc.)
│  │  ├── Kernel.php                      # Middleware pipeline executor
│  │  ├── MenuLoader.php                  # Builds sidebar menu from manifests
│  │  ├── Middleware.php                  # Middleware interface
│  │  ├── Request.php                     # HTTP request abstraction
│  │  ├── Response.php                    # HTTP response handler
│  │  ├── Router.php                      # Lightweight route dispatcher
│  │  └── View.php                        # Modular view resolver (isolates per-app Views/)
│  └─ Http/
│     └─ Middleware/
│         ├── AuthRequired.php             # Restricts routes to authenticated users
│         ├── ErrorCatcher.php             # Catches exceptions, renders proper error pages
│         ├── GuestOnly.php                # Restricts login routes for authenticated users
│         ├── RequirePermission.php        # RBAC middleware for permission checks
│         └── TrailingSlash.php            # Redirects /foo/ → /foo
├─ storage/
│  ├─ logs/
│  │  └── app.log                         # Error log file
│  └─ sessions/                           # PHP session storage
├─ tests/                                 # (future) PHPUnit tests
├─ composer.json                          # Autoload + dependencies
├─ composer.lock
├─ favicon.ico                            # Browser tab icon
├─ CHANGELOG.md                           # Version and changes history
├─ RBAC.md                                # Detailed RBAC technical documentation
├─ DB.md                                  # Database schema and migration notes
└─ README.md                              # This file – developer documentation
```


## Composer Setup

`composer.json`:

```json
{
  "name": "unitarius/framework",
  "description": "Minimal PHP microframework for Unitarius",
  "license": "MIT",
  "require": {
    "php": "^8.3",
    "vlucas/phpdotenv": "^5.6",
    "google/apiclient": "^2.15"
  },
  "autoload": {
    "psr-4": {
      "App\": "app/",
      "Core\": "src/Core/",
      "Http\": "src/Http/"
    }
  }
}
```

Install + dump autoload:
```sh
composer install
composer dump-autoload -o
```

---

## .env Example

```
APP_ENV=local   # or production
APP_URL=http://localhost/unitarius   # local dev URL
# APP_BASE_PATH=/unitarius   # only if app runs in a subfolder

DB_HOST=localhost
DB_PORT=5432
DB_NAME=org_unitarius_adatbazis
DB_USER=postgres
DB_PASS=postgres

# Google OAuth
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost/unitarius/auth/google/callback
```

---

## Apache Setup (Wamp)

- Enable `rewrite_module`
- In `httpd.conf` and `httpd-ssl.conf`:

```apache
<Directory "C:/wamp/www/">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<Directory "C:/wamp/www/unitarius/">
    AllowOverride All
    Require all granted
</Directory>
```

- `.htaccess` (in project root):

```apache
Options -Indexes -MultiViews
DirectoryIndex public/index.php

RewriteEngine On
RewriteBase /unitarius/

RewriteRule ^(app|src|storage|tests|vendor)/ - [F,L]
RewriteRule ^(\.env|composer\.(json|lock)) - [F,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

RewriteRule ^ public/index.php [QSA,L]
```

---

## Core Components Implemented

- Router.php – minimal GET routing, works from subfolder /unitarius/.  
- ErrorHandler.php – unified error/exception handling, logs to storage/logs/app.log.  
- DB.php – PDO wrapper for PostgreSQL connection.  
- View.php – plain PHP view rendering with layout support.  
  > View engine is plain PHP. No `$this->extend()` / `$this->section()` support.  
  > `View::render('name', $data)` always injects view into `layout.php` via `$content`.  
- Request.php – normalized wrapper for HTTP request.  
- Response.php – normalized wrapper for HTTP response.  
- Middleware.php – interface for middleware.  
- Kernel.php – middleware pipeline runner.  
- **MenuLoader.php** – scans per-app manifests, sets defaults (`icon`, `order`, `match`), sorts children by order + label.  

---

## Example Controller

`app/Controllers/DashboardController.php`:

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\DB;
use Core\View;

final class DashboardController
{
    public function index(): string
    {
        $rows = [];
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->query('SELECT 1 AS id, CURRENT_DATE AS today');
            $rows = $stmt->fetchAll() ?: [];
        } catch (\Throwable) {
            // swallow for demo; ErrorHandler already logs in local
        }

        return View::render('dashboard/index', [
            'title' => 'Unitarius – Vezérlőpult',
            'rows'  => $rows,
        ]);
    }
}
```

---

## Middleware

- ErrorCatcher – catches exceptions and shows errors/500.php (with fallback).  
- TrailingSlash – normalizes `/foo/` → `/foo`.  

Usage in `public/index.php`:

```php
$kernel = new \Core\Kernel();
$kernel->push(new \Http\Middleware\ErrorCatcher());
$kernel->push(new \Http\Middleware\TrailingSlash());

$req = new \Core\Request();
$res = $kernel->handle($req, function($r) use ($router) {
    $html = $router->dispatch($r->method(), $r->uri());
    return (new \Core\Response())->html($html);
});
$res->send();
```

---

## Dynamic Sidebar (AdminLTE)

- Each app defines its own `manifest.php` with: `name`, `label`, `icon`, `prefix`, `order`, `match`, `children`.  
- Children support individual icons, order, and `perm`.  
- `MenuLoader` merges manifests into a menu structure, applies defaults:  
  - parent default icon: `fa-regular fa-folder`  
  - child default icon: `fa-regular fa-circle`  
  - default order: `999`  
- Sidebar renders dynamically:  
  - If an item has children → parent acts as a **toggler only**, not a link.  
  - Children sorted by `order`, then label.  
- Future: integrate `can($perm)` for RBAC filtering.

---

## Per-App Manifest (Menu Metadata)

Each app under `app/Apps/*` defines its own `manifest.php`.  
The manifest declares how the app appears in the dynamic AdminLTE sidebar.

### Schema (per top-level item)

- `name` *(string, required)* – unique internal key (`people`, `users`)  
- `label` *(string, required)* – human readable menu name (HU text)  
- `prefix` *(string, required)* – URL prefix (e.g. `/people`)  
- `icon` *(string, required)* – Font Awesome icon class (`fa-solid fa-users`)  
- `order` *(int, optional)* – menu order (default `999`)  
- `match` *(array, optional)* – regex patterns to determine “active” state  
- `perm` *(string|null, optional)* – RBAC requirement (future use)  
- `children` *(array, optional)* – submenu items  

### Schema (child item)

- `label` *(string, required)* – child menu label  
- `url` *(string, required)* – child absolute URL (use `base_url('/path')`)  
- `match` *(array, required)* – regex patterns for active-state  
- `icon` *(string, optional)* – child icon class (default: `fa-regular fa-circle`)  
- `order` *(int, optional)* – child order (default `999`)  
- `perm` *(string|null, optional)* – RBAC requirement (future use)  

### Example: `app/Apps/People/manifest.php`

```php
<?php
declare(strict_types=1);

return [
    'name'    => 'people',
    'label'   => 'Személyek',
    'icon'    => 'fa-solid fa-users',
    'prefix'  => '/people',
    'order'   => 10,
    'match'   => ['#^/people#'],
    'perm'    => null,
    'children'=> [
        [
            'label' => 'Lista',
            'url'   => base_url('/people'),
            'match' => ['#^/people$#'],
            'icon'  => 'fa-regular fa-list',
            'order' => 1,
            'perm'  => null,
        ],
        [
            'label' => 'Új személy',
            'url'   => base_url('/people/new'),
            'match' => ['#^/people/new#'],
            'icon'  => 'fa-regular fa-plus',
            'order' => 2,
            'perm'  => null,
        ],
    ],
];
```
Notes

If an item has children → parent acts as a toggler only, not a link.

Parents/children are sorted by order, then label.

Default icons:

Parent: fa-regular fa-folder

Child: fa-regular fa-angles-right

RBAC integration (perm) will be applied later using can($perm).


---
## RBAC (Role-Based Access Control)

The system will use **RBAC** to manage permissions across multiple modules/programs.  
This approach is simpler and more scalable than per-user ACLs.



### Concepts
- **Roles** – groups of permissions (e.g. `admin`, `editor`, `viewer`).
- **Permissions** – fine-grained rights, namespaced per module (e.g. `hr.person.create`, `finance.invoice.view`).
- **Members ↔ Roles** – many-to-many relation between users and roles.
- **Role ↔ Permissions** – many-to-many relation between roles and permissions.

### Benefits
- Centralized role management.
- Easy to add new modules: define new permissions with a prefix (e.g. `programX.*`).
- Scales better as the system grows.
- Can be extended with ABAC (attribute-based) checks later.

### Planned seed roles
- **Admin** – full access.
- **Editor** – can create/update but not manage roles.
- **Viewer** – read-only.

### Next ideas:
- GUI with AdminLTE V4  
- Config management (`config/app.php`, `config/db.php`) instead of direct `.env` usage everywhere.  
- Logger class (e.g. `Logger::info()`, `Logger::error()`) with timestamp, env, request ID.  
- Validator (basic form validation).  
- Security middleware (CSRF tokens, security headers).  
- (Optional) CLI commands (e.g. `bin/console migrate`).  

### RBAC Admin UI (current implementation)

The RBAC administration module (`app/Apps/Users/Controllers/RbacController.php` + `views/rbac/*`) provides a full CRUD interface for roles, permissions, and assignments.

#### Features implemented
- **Flash messages** (`flash_set('success'|'error', '...')`) after create/edit/delete/attach/detach.
- **CSRF protection** on all POST actions (`verify_csrf()` and `csrf_field()`).
- **Assignments view** (`assignments.php`):
  - Two attach forms:
    - *User ↔ Role*
    - *Role ↔ Permission*
  - Inline detach buttons with confirmation prompts.
  - Searchable select fields using **Choices.js**.
  - Pagination and sorting for both lists (user↔role and role↔permission).
  - Query parameters preserved on navigation:
    ```
    ur_page, ur_per, ur_sort, ur_dir
    rp_page, rp_per, rp_sort, rp_dir
    ```
- **Default pagination limits:** 10 / 25 / 50 / 100 per page.
- **Sorting** with clickable table headers and caret icons (`fa-caret-up`, `fa-caret-down`, `fa-sort`).
- **Consistent layout** (AdminLTE 4 cards, tables, responsive container).

#### Technical details
- `RbacController::assignments()` now:
  - Performs safe pagination and sorting (whitelist-based ORDER BY).
  - Provides `$urPager`, `$rpPager`, `$urSort`, `$urDir`, `$rpSort`, `$rpDir` to the view.
- `views/rbac/assignments.php`:
  - Generates URLs with preserved query parameters (`$buildUrl()` helper).
  - Displays range text: “start–end / total”.
  - Dropdown for selecting items per page.
  - Fully localized Hungarian UI texts.

#### Future improvements
- Local copy of **Choices.js** instead of CDN.
- Adjust dark-mode dropdown colors for better contrast.
- Optional “Reset sort” button per table.
- Self-demote and “keep one admin” guards for detach operations.

### 🔒 RBAC Security Guards

To prevent accidental loss of administrative access, two safety guards were implemented in the RBAC system:

1. **Self-demote guard**  
   Prevents an administrator from removing their own `admin` role.  
   If the logged-in admin attempts to detach their own admin assignment, the action is blocked and an error message is displayed.

2. **Keep-one-admin guard**  
   Ensures that at least one administrator always exists in the system.  
   - Blocks detaching the last remaining `admin` role from any user.  
   - Also prevents deleting the `admin` role itself if it is still assigned to users.

These checks are enforced in:
- `RbacController::assignmentsDetach()` → user↔role operations  
- `RbacController::roleDelete()` → role deletion operations  

Both guards display localized flash messages (AdminLTE alerts) and redirect safely back to the RBAC management interface.


---

## Authentication & Sessions

### Session bootstrap
- Implemented in `public/index.php`.
- Secure cookie parameters: `httponly`, `samesite=Lax`, `secure` (if HTTPS).
- Session files stored in `storage/sessions/`.

### Auth helpers
Added to `src/Core/Helpers.php`:
- `current_user(): ?array` — returns logged in user or null.
- `is_logged_in(): bool` — quick check for authentication state.
- `login_user(array $user): void` — regenerates session id, stores user payload, updates `last_login_at`.
- `logout_user(): void` — clears session and destroys cookie.

### Middleware
New middleware classes in `src/Http/Middleware/`:
- `AuthRequired` — protects routes, redirects unauthenticated users to `/login`.
- `GuestOnly` — prevents logged in users from accessing `/login`.

Both implement `Core\Middleware` interface.

### AuthController
File: `app/Controllers/AuthController.php`  
Provides:
- `showLogin()` — renders login form (`app/Views/auth/login.php`).
- `doLogin()` — validates credentials, logs user in via helpers.
- `logout()` — logs user out and redirects to `/login`.

### Routes
Defined in `public/index.php`:
- `GET /` → AuthController::index
- `GET /login` → AuthController::showLogin
- `POST /login` → AuthController::doLogin
- `POST /logout` → AuthController::logout
- `GET /logout` → AuthController::logout
- `GET /auth/google` → AuthController::googleRedirect
- `GET /auth/google/callback` → AuthController::googleCallback


### Users table
PostgreSQL schema (simplified):
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status SMALLINT NOT NULL DEFAULT 1,
    AVATAR TEXT,
    last_login_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

Test user example (password: `tengerimalac`):
```sql
INSERT INTO users (email, password_hash, name, status, created_at, updated_at)
VALUES (
  'kovacszsoltsp@gmail.com',
  crypt('tengerimalac', gen_salt('bf', 12)),
  'Admin',
  1,
  NOW(),
  NOW()
);
```

## Google Auth specific settings, tweaks 
Google avatar images require `referrerpolicy="no-referrer"` to display correctly, 
> since Google blocks requests with referrer headers from external domains.


### Google first-login behavior
- When a user signs in with Google for the first time and no local record exists,
  the system automatically creates a new user entry:
    - `email` — from Google profile  
    - `name` — from Google profile (or email if missing)  
    - `avatar` — Google profile image (TEXT URL)  
    - `status` — set to 1 (active)  
    - `password_hash` — random, securely generated string (not disclosed to user)  
    - `created_at`, `updated_at`, `last_login_at` — set to `NOW()`
- On subsequent Google logins, only `name`, `avatar`, and `last_login_at`
  are updated if they changed.


### Logout security
- The logout process clears all session data, including OAuth session variables.
- It regenerates the session ID and invalidates the session cookie to prevent fixation.


### Debug mode
When `APP_ENV=local` or `?__debug=1` is added to a URL, 
AuthController methods emit extra headers (e.g., `X-Debug-Token-HasError`)
and display raw exception traces to aid troubleshooting.

#### RBAC helper functions

- `can($perm)` – returns true when the logged-in user has the given permission (wildcards supported).
- `can_any([$perm1, $perm2, ...])` – returns true if at least one of the permissions matches.
- `require_can($perm)` – throws a 403 error and halts execution if the permission is missing (handy in controllers or at the top of routes for a quick guard).
- `require_owner()` – helper for validating record ownership.

### 🧩 Core\View – Modular View Resolution

The `Core\View` class handles all template rendering.  
After the recent refactor, the system now automatically detects **which app** invoked `View::render()` and loads the correct view file accordingly.

#### How it works

- Uses `debug_backtrace()` to identify the calling controller (e.g. `App\Apps\Users\Controllers\UserController`).
- Extracts the app name (`Users`) and searches for views inside  
  `app/Apps/{AppName}/Views/{view}.php`.
- If the file is not found in the app's directory, it falls back to the global location:  
  `app/Views/{view}.php`.

This change ensures full isolation between app-level views —  
for example, `Users/Views/list.php` and `People/Views/list.php` can now coexist without conflicts.


## 🧩 User Management Permissions (RBAC Policy)

| Permission key | Description | Typical role |
|-----------------|--------------|---------------|
| **users.view** | Allows viewing the user list and profile details. | `member`, `manager`, `admin` |
| **users.manage** | Allows editing other users’ profiles and changing their status (active/inactive). | `manager`, `admin` |
| **users.create** | Allows creating new user accounts. | `manager`, `admin` |
| **users.delete** | Allows deleting user accounts (hard or soft delete). Should be restricted to admins only. | `admin` |

> **Notes**
> - Regular users (members) can always edit their own profile without any explicit permission.  
> - The `users.manage` permission does *not* include delete rights.  
> - Deletion (`users.delete`) must be explicitly granted — typically only to admins.

---

## 🔒 RBAC Role Deletion Safety (Extended)

A new safety rule has been added to the RBAC system.

Previously, only the `admin` role was protected from deletion while still assigned to users.
Now, **no role can be deleted if it is currently assigned to any user**.

This prevents accidental permission loss or orphaned user-role links.

```php
// Inside RbacController::roleDelete()
if ($roleName !== '') {
    $count = (int)$pdo
        ->query('SELECT COUNT(*) FROM user_roles WHERE role_id = ' . $id)
        ->fetchColumn();

    if ($count > 0) {
        \flash_set('error', 'Cannot delete a role while it is assigned to users.');
        header('Location: ' . base_url('/rbac/roles'));
        exit;
    }
}
```

All deletion attempts are logged via flash messages and safely redirected back to the roles page.

---

## 🧬 Users Module – Task List (Next Phase)

### ✅ Completed

* Basic user listing (`/users`) with avatars, email, status, and last login.
* RBAC permission setup (`users.view`, `users.manage`, `users.create`, `users.delete`).
* View resolver fix (App-based isolation for multiple `list.php` files).

---

## 🧩 Users Module – Soft Delete & Admin Tools

### ✅ Implemented Features

#### Soft Delete System
The Users module now supports **soft deletion** instead of permanent record removal.

| Field | Type | Description |
|-------|------|-------------|
| `deleted` | SMALLINT (0/1) | Marks user as deleted |
| `deleted_at` | TIMESTAMPTZ | Timestamp of deletion |
| `status` | SMALLINT | 1 = active, 0 = inactive |

**Behavior:**
- When a user is deleted → `deleted = 1`, `deleted_at = NOW()`, `status = 0`
- When restored → `deleted = 0`, `deleted_at = NULL`, `status = 0` (remains inactive)

#### Deleted Users Admin Panel
- Added a new Admin-only page: `/users/deleted`
- Permission required: `users.admin`
- Lists only users where `deleted = 1`
- Accessible from the sidebar with a **red “user-slash” icon**
- Only Admins can view and restore deleted users

#### User Restore
- Endpoint: `POST /users/{id}/restore`
- Function: `UserController::restore()`
- Restores user to **inactive** status
- Flash message on success or failure
- Redirects to `/users/deleted`

#### Role Safety Integration
- When a user is deleted, they are marked inactive instead of being removed from `user_roles`.
- The RBAC “keep-one-admin” rule remains consistent, ensuring safe permission structure.
- Future improvement: automatically revoke roles for deleted users to allow safe role deletion.

#### Database Update Example
```sql
ALTER TABLE users 
ADD COLUMN deleted SMALLINT NOT NULL DEFAULT 0,
ADD COLUMN deleted_at TIMESTAMPTZ NULL;

---

### 🧩 In Progress / To Do

#### 1. Edit functionality

* Add "Edit" button to the user list (visible only to `users.manage` or higher).
* Implement `UserController::editForm()` and `UserController::editSave()` methods.
* Add `Views/edit.php` form (similar layout to RBAC forms).
* Adjust button visibility based on permissions (after RBAC checks).

#### 2. Create functionality

* Add “New user” button (visible only to `users.create`).
* Implement `UserController::createForm()` and `UserController::createSave()`.

#### 3. Delete functionality

* Add delete button (visible only to `users.delete`).
* Prefer soft-delete (mark user as inactive) instead of full deletion.

#### 4. Flash & validation

* Add validation for name, email, and status fields.
* Show flash success/error messages after create/edit/delete.

#### 5. Profile editing (self)

* Add `/profile` route for logged-in user.
* Allow self-edit of name, avatar, and password (no role or status changes).
* Add “Edit profile” button to the top-right user menu.

---

### 🎨 UI & Layout improvements

#### RBAC (Roles/Permissions) pages

* Reduce or hide `created_at` and `updated_at` columns (too wide).
* Expand the `label` (description) column for readability.

#### Users list

* Add search bar and pagination.
* Add “Reset sort” or “Show all” button.
* Persist sorting preferences via query parameters (`ur_sort`, `rp_sort`).
* Possibly remove avatars to save space.
* Replace multiple inline buttons with a compact **Actions dropdown**.
* Use **filled buttons** (no transparent/outline buttons across UI).

#### Edit page

* Place action buttons **below** the permissions section.
* Ensure consistent AdminLTE color scheme (primary/success/danger).
* All buttons use filled background style.

---

### 🧮 Optional Enhancements

* Filter users by status (active/inactive).
* Integrate Choices.js dropdowns for roles and status.
* Future: connect users ↔ people table (optional foreign key).

---

## 🏷️ RBAC Permission Label as Description

In the database, the `label` field of a permission or role can be used as a **human-readable description**.
It supports long, descriptive Hungarian text, for example:

| Permission     | Label (Description)                                   |
| -------------- | ----------------------------------------------------- |
| `users.manage` | Mások adatainak szerkesztése, státusz módosítása      |
| `users.view`   | Felhasználók listájának megtekintése                  |
| `rbac.manage`  | Szerepek és jogosultságok kezelése az admin felületen |

This allows the admin UI to show meaningful, localized descriptions without changing the internal permission names.

** 🔒 Login and Status Behavior

## Inactive Users

* Both local and Google login now deny access if status = 0.
* Error message: “A fiók inaktív. Kérlek, vedd fel a kapcsolatot az adminisztrátorral.”

Google Login Improvements

* If user exists in DB and is inactive → no new user is created
* The system now respects status and deleted flags consistently across both login flows
avatar and name fields are updated only on first login or if empty in DB

### 🧩 Adatlap Module – RBAC Permissions

| Permission Key            | Description | Typical Role |
|----------------------------|-------------|---------------|
| **adatlap.lelkesz**        | Allows a pastor to view and edit only their own family records (ownership-based access). | `pastor` |
| **adatlap.family.add**     | Allows creating new family records in the database (admin/secretary level access). | `admin`, `secretary` |
| **adatlap.family.manage**  | Grants full management rights over all family records, including updates and soft deletes. | `admin` |

**Note:**  
The `families` table includes `created_by_uuid` and `updated_by_uuid` audit fields.  
These automatically store the UUID of the user who created or last modified each record.  
Users with only the `adatlap.lelkesz` permission can access **only** the families they created,  
while users with `adatlap.family.add` or `adatlap.family.manage` can view or edit all family records.
