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
├── app/                 # Application layer (controllers, views)
│   ├── Controllers/
│   │   ├── HomeController.php   # Renders home page, tests DB
│   │   └── HelloController.php # Request/Response test (no DB)
│   ├── Views/
│   │   ├── layout.php   # Base layout template
│   │   ├── home.php     # Home page view
│   │   ├── hello.php    # Hello demo view
│   │   └── partials/
│   │       ├── navbar.php      # AdminLTE top navbar
│   │       └── sidebar.php     # AdminLTE sidebar (base_url + active link)
│   └── errors/
│       ├── 404.php      # Not found page
│       └── 500.php      # Error page (used by ErrorCatcher middleware)
├── config/              # (future) central configs
├── public/
|   ├── assets/
|   |   └─ adminlte/           # AdminLTE static assets V4
│   │       ├── css/            # AdminLTE CSS (adminlte.min.css, stb.)
│   │       ├── js/             # AdminLTE JS (adminlte.min.js, stb.)
│   │       └── img/            # AdminLTE images (boxed-bg.jpg, avatar.png, stb.)
│   ├── index.php        # Front controller (bootstrap + router/kernel)
│   └── .htaccess        # Apache rewrite rules
├── src/
│   ├── Core/
│   │   ├── Router.php          # Minimal GET router
│   │   ├── ErrorHandler.php    # Unified error/exception handling
│   │   ├── DB.php              # PDO wrapper for PostgreSQL
│   │   ├── View.php            # PHP view rendering with layout
│   │   ├── Request.php         # HTTP request wrapper
│   │   ├── Response.php        # HTTP response wrapper
│   │   ├── Middleware.php      # Middleware interface
│   │   ├── Kernel.php          # Middleware pipeline runner
│   │   └── Helpers.php         # Global helpers (e.g. base_url() using APP_URL)
│   └── Http/
│       └── Middleware/
│           ├── ErrorCatcher.php # Catches exceptions, shows 500 page
│           └── TrailingSlash.php # Redirects /foo/ → /foo
├── storage/
│   └── logs/
│       └── app.log             # Error log
├── tests/                      # (future) PHPUnit tests
├── composer.json               # Composer config (autoload, deps)
└── .env                        # Local environment config
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

app/Controllers/HomeController.php:

```php
final class HomeController
{
    public function index(): string
    {
        $rows = [];
        try {
            $pdo = \Core\DB::pdo();
            $stmt = $pdo->query('SELECT version() AS pg_version');
            $rows = $stmt->fetchAll() ?: [];
        } catch (\Throwable) {}

        return \Core\View::render('home', [
            'title' => 'Unitarius – Kezdőlap',
            'rows'  => $rows,
        ]);
    }
}
```

app/Controllers/HelloController.php (no DB, test Request/Response):

```php
final class HelloController
{
    public function greet(\Core\Request $r): \Core\Response
    {
        $name = trim(basename($r->uri()));
        $html = \Core\View::render('hello', [
            'title' => 'Üdvözlet',
            'name'  => $name ?: 'Világ'
        ]);
        return (new \Core\Response())->html($html);
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
