# Changelog – Unitarius PHP Microframework

Minden jelentősebb változás és fejlesztési lépés dokumentálása.

---

## [0.1.0] – Alap indulás
- Projekt gyökér létrehozva: `C:/wamp/www/unitarius`
- Composer inicializálva, autoload beállítások (PSR-4: `App\`, `Core\`)
- `.env` támogatás hozzáadva (vlucas/phpdotenv)

## [0.1.1] – Alap szerver és .htaccess
- Wamp `rewrite_module` engedélyezve
- `httpd.conf` és `httpd-ssl.conf` módosítva: `AllowOverride All`
- Root `.htaccess` létrehozva, amely minden kérést a `public/index.php`-ra irányít

## [0.2.0] – Router és Controller
- `src/Core/Router.php` létrehozva – minimál GET routing
- `app/Controllers/HomeController.php` létrehozva
- `public/index.php` betölti a Routert és a controllert

## [0.2.1] – ErrorHandler
- `src/Core/ErrorHandler.php` hozzáadva
- Exception és error kezelő regisztrálás
- Fejlesztői módban részletes trace, élesben 500 Internal Server Error
- Logolás a `storage/logs/app.log`-ba

## [0.3.0] – PostgreSQL kapcsolat
- `src/Core/DB.php` létrehozva PDO wrapperként
- .env-ben DB beállítások (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`)
- HomeController `SELECT version()` teszt

## [0.4.0] – Nézet rendszer
- `src/Core/View.php` létrehozva
- `app/Views/layout.php` és `app/Views/home.php` létrehozva
- HomeController a `View::render()`-t használja
- Javítva a view path (`dirname(__DIR__, 2)`)

---

## Következő tervezett lépések
- Adatbázis táblák definiálása (pl. members, units, events)
- Paraméteres route-ok (`/hello/{name}`)
- Alap navigáció a layoutban
- PSR-12 kódstílus ellenőrzés (phpcs)
