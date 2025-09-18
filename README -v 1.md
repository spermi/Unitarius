# Unitarius PHP Microframework

Minimal PHP microframework built from scratch for learning and internal use.

---

## Environment

- Windows + VSCode
- WampServer (Apache + PHP 8.3.14)
- PostgreSQL
- Composer 2.8.11

---

## Project Structure

```
unitarius/
  app/
    Controllers/
      HomeController.php
    Views/
      layout.php
      home.php
  config/              (future)
  public/
    index.php
    .htaccess
  src/
    Core/
      Router.php
      ErrorHandler.php
      DB.php
      View.php
  storage/
    logs/
      app.log
  tests/               (future)
  composer.json
  .env
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
      "Core\\": "src/Core/"
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
APP_ENV=local
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

- **Router.php** – minimal GET routing, works from subfolder `/unitarius/`.
- **ErrorHandler.php** – unified error/exception handling, logs to `storage/logs/app.log`.
- **DB.php** – PDO wrapper for PostgreSQL connection.
- **View.php** – plain PHP view rendering with layout support.

---

## Example Controller

`app/Controllers/HomeController.php`:
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

---

## Next Steps

- Define real database tables (e.g. members, units, events).
- Add parametric routes (`/hello/{name}`).
- Create basic navigation in layout.
- Optional: introduce PSR-12 coding standard check (phpcs).
