# Unitarius PHP Microframework

Minimal PHP microframework built from scratch for learning and internal use.

---

## Development Environment 

- Windows + VSCode
- WampServer (Apache + PHP 8.3.14)
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
│  │  │  └─ manifest.php                  # Menu/App meta: label, icon, prefix, order, [children], [perm]
│  │  ├─ Users/                           # Users + (later) RBAC mini-app
│  │  │  ├─ Controllers/
│  │  │  │  └─ UserController.php         # User listing & management
│  │  │  ├─ Views/
│  │  │  │  ├─ list.php                   # Users list view
│  │  │  │  └─ rbac.php                   # Placeholder for RBAC UI (roles/permissions)
│  │  │  ├─ routes.php                    # Users app routes
│  │  │  └─ manifest.php                  # Menu/App meta (can define children: Users, RBAC, etc.)
│  │  └─ (more apps as needed)/
│  ├─ Controllers/
│  │  └─ DashboardController.php          # Global dashboard (home)
│  ├─ Views/
│  │  ├─ layout.php                       # Global AdminLTE layout (header/sidebar/content/footer)
│  │  ├─ partials/
│  │  │  ├─ navbar.php                    # AdminLTE top navbar (brand, user menu)
│  │  │  ├─ sidebar.php                   # AdminLTE sidebar; renders menu from MenuLoader
│  │  │  └─ breadcrumbs.php               # Simple breadcrumbs (optional)
│  │  └─ dashboard/
│  │     └─ index.php                     # Dashboard landing page content
│  └─ errors/
│     ├─ 404.php                          # Not found page
│     └─ 500.php                          # Error page (used by ErrorCatcher)
├─ config/
│  ├─ apps.php                            # App registry (optional if you fully rely on manifest scanning)
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
│  │  └─ MenuLoader.php                   # Scans app manifests, builds menu structure (with defaults)
│  └─ Http/
│     └─ Middleware/
│        ├─ ErrorCatcher.php              # Catches exceptions, renders 500 (or JSON later)
│        └─ TrailingSlash.php             # Redirects /foo/ → /foo
├─ storage/
│  └─ logs/
│     └─ app.log                          # Application error log
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
    "vlucas/phpdotenv": "^5.6"
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Core\\": "src/Core/",
      "Http\\": "src/Http/"
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
APP_ENV=local  # or production 
DB_HOST=localhost
DB_PORT=5432
DB_NAME=org_unitarius_adatbazis
DB_USER=postgres
DB_PASS=postgres
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

-Router.php – minimal GET routing, works from subfolder /unitarius/.
-ErrorHandler.php – unified error/exception handling, logs to storage/logs/app.log.
-DB.php – PDO wrapper for PostgreSQL connection.
-View.php – plain PHP view rendering with layout support.
```-A view engine plain PHP. Nincs $this->extend() / $this->section() támogatás.
  A View::render('name', $data) mindig beágyazza a nézetet a layout.php-ba a $content változón keresztül.
 ```
-Request.php – normalized wrapper for HTTP request.
-Response.php – normalized wrapper for HTTP response.
-Middleware.php – interface for middleware.
-Kernel.php – middleware pipeline runner.

---

## Example Controllers

app/Controllers/DashboardController.php:

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
        // Example: read a small sample (adjust table/columns later)
        $rows = [];
        try {
            $pdo = DB::pdo();
            // TODO: replace with your real table
            $stmt = $pdo->query('SELECT 1 AS id, CURRENT_DATE AS today');
            $rows = $stmt->fetchAll() ?: [];
        } catch (\Throwable) {
            // swallow for demo; ErrorHandler already logs in local
        }

        return View::render('dashboard/index', [
            'title' => 'Unitarius – Kezdőlap',
            'rows'  => $rows,
        ]);
    }
}

```

---

Middleware

ErrorCatcher – catches exceptions and shows errors/500.php (with fallback).

TrailingSlash – normalizes /foo/ → /foo.

Usage in public/index.php:

``` php
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

### Kovetkezo lepesek , otletek :
- Grafikus felulet:
	 AdminLTE V4
- Konfiguráció kezelés
	Külön config/ mappa, pl. config/app.php, config/db.php.
	Így nem mindenhol közvetlenül az .env-ből olvasunk, hanem van egy központi config loader.
- Logging
	Most van storage/logs/app.log, de lehetne egy Logger osztály.
	Példa: Logger::info(), Logger::error() → automatikus timestamp, környezet, request ID.
- Validator
	Alap input validálás (kötelező mező, email, hossz).
	Hasznos, ha formokat kezdünk kezelni.
- Security middleware
	Pl. CsrfMiddleware token ellenőrzés POST-hoz.
	SecurityHeadersMiddleware alap HTTP headerekkel.
- Egyszerű CLI parancsok (talan nem kell )
	bin/console script, amivel futtathatsz: php bin/console migrate, php bin/console cache:clear, stb.
