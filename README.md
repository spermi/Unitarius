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
│  │  │  ├─ Controllers/                  # Controllers for People module
│  │  │  │  └─ PersonController.php       # Example controller (list/create/show)
│  │  │  ├─ Views/                        # Views for People module (rendered into global layout)
│  │  │  │  ├─ list.php                   # People list view
│  │  │  │  └─ form.php                   # Create/Edit form view
│  │  │  ├─ routes.php                    # Routes local to People (mounted under prefix from manifest)
│  │  │  └─ manifest.php                  # Menu/App meta: label, icon, prefix, order, children, perm
│  │  ├─ Users/                           # Users + (later) RBAC mini-app
│  │  │  ├─ Controllers/                  # Controllers for user and RBAC management
│  │  │  │  ├─ UserController.php         # User listing & management
│  │  │  │  └─ RbacController.php         # RBAC (roles, permissions, assignments)
│  │  │  ├─ Views/                        # Views for Users and RBAC modules
│  │  │  │   ├─ rbac/                     # RBAC section views
│  │  │  │   │  ├─ assignments.php        # Lists user↔role and role↔permission mappings
│  │  │  │   │  ├─ index.php              # RBAC dashboard (entry point)
│  │  │  │   │  ├─ permissions.php        # Permission list view
│  │  │  │   │  └─ roles.php              #    list view
│  │  │  │   └─ Users/                    # User section views
│  │  │  │       └─ list.php              # Users list view
│  │  │  ├─ routes.php                    # Users app routes (with RBAC middleware)
│  │  │  └─ manifest.php                  # Per-app manifest (menu meta, children: Users, RBAC)
│  │  └─ (more apps as needed)/
│  ├─ Controllers/
│  │  └─ DashboardController.php          # Global dashboard (replaces old HomeController)
│  │  └─ AuthController.php               # Handles login form, login submission, logout
│  ├─ Views/
│  │  ├─ layout.php                       # Global AdminLTE layout (header/sidebar/content/footer)
│  │  ├─ login.php                        # standalone full-page view for the login screen; independent from the main `layout.php`.
│  │  ├─ partials/
│  │  │  ├─ navbar.php                    # AdminLTE top navbar (brand, user menu)
│  │  │  ├─ sidebar.php                   # AdminLTE sidebar; dynamic, built via MenuLoader
│  │  │  └─ breadcrumbs.php               # Simple breadcrumbs (optional)
│  │  ├─ dashboard/
│  │  │  └─ index.php                     # Dashboard landing page content
│  └─ errors/
│     ├─ 404.php                          # Not found page
│     └─ 500.php                          # Error page (used by ErrorCatcher)
├─ config/
│  ├─ apps.php                            # (Optional) App registry if not only manifest-based
│  └─ menu.core.php                       # Core (non-app) menu entries, e.g., Dashboard
├─ public/
│  ├─ assets/
│  │  └─ adminlte/                        # AdminLTE V4 static assets (local copy)
│  │     ├─ css/                          # AdminLTE & dependencies CSS
│  │     │  ├─ adminlte.min.css           # Core AdminLTE CSS (minified)
│  │     │  └─ adminlte.css               # (optional) Non-minified version for dev
│  │     ├─ js/                           # AdminLTE & dependencies JS
│  │     │  ├─ adminlte.min.js            # Core AdminLTE JS (minified)
│  │     │  └─ adminlte.js                # (optional) Non-minified version for dev
│  │     ├─ img/                          # Images used by the template (logos, placeholders)
│  │     │  ├─ avatar.png                 # Example user avatar used in UI
│  │     │  └─ boxed-bg.jpg               # Example boxed layout background
│  │     └─ (optional extras)             # e.g., webfonts/plugins if you add them later
│  ├─ index.php                           # Front controller (bootstrap + router/kernel)
│  └─ .htaccess                           # Rewrite rules (route all to public/index.php)
├─ src/
│  ├─ Core/
│  │  ├─ Router.php                       # Minimal router with support for mounting app routes
│  │  ├─ ErrorHandler.php                 # Unified error/exception handling
│  │  ├─ DB.php                           # PDO wrapper for PostgreSQL
│  │  ├─ View.php                         # PHP view rendering with base layout injection
│  │  ├─ Request.php                      # HTTP request abstraction
│  │  ├─ Response.php                     # HTTP response abstraction
│  │  ├─ Middleware.php                   # Middleware interface
│  │  ├─ Kernel.php                       # Middleware pipeline runner
│  │  ├─ Helpers.php                      # Global helpers (e.g., base_url(), base_path())
│  │  └─ MenuLoader.php                   # Scans app manifests, builds menu; child order + defaults
│  └─ Http/
│     └─ Middleware/
│        ├─ ErrorCatcher.php              # Catches exceptions, renders 500 (or JSON later)
│        ├─ TrailingSlash.php             # Redirects /foo/ → /foo
│        ├─ AuthRequired.php              # Middleware: protect routes, require authentication
│        └─ GuestOnly.php                 # Middleware: only guests allowed, redirect if logged in
├─ storage/
│  ├─ logs/
│  │  └─ app.log                          # Application error log
│  └─ sessions/                           # PHP session file storage
├─ tests/                                 # (future) PHPUnit tests
├─ composer.json                          # Autoload + deps (phpdotenv, etc.)
├─ .env                                   # Local environment config (APP_ENV, DB_*)
└─ README.md                              # Keep updated with structure/menu changes
```

---

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

### next work

legalább egy admin maradjon mindig a rendszerben

admin ne vehesse el a saját admin jogát (self-demote guard)

require_can($perm) – 403 hibát dob, ha a felhasználónak nincs jogosultsága

require_owner() helper rekord-tulajdonjog ellenőrzéshez

flash üzenetek redirect után

szép 403 / 404 / 500 hibasablonok (AdminLTE stílusban)

Application admin modul (app-, menü-, route-, manifest-kezelés)

MenuLoader DB-fallback / export-import

local user létrehozás támogatása

e-mail / SMTP konfiguráció