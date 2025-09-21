<?php
declare(strict_types=1);

namespace App\Apps\People\Controllers;

use Core\DB;
use Core\View;
use PDO;

final class PeopleController
{
    public function index(): string
    {
        $pdo = DB::pdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // PostgreSQL: concat_ws + ::text cast az enumokra
        $sql = "
            SELECT
                person_id,
                COALESCE(display_name, trim(concat_ws(' ', given_name, middle_name, family_name))) AS fullname,
                given_name,
                middle_name,
                family_name,
                birth_date,
                gender::text  AS gender,
                status::text  AS status,
                created_at
            FROM persons
            ORDER BY created_at DESC, person_id DESC
            LIMIT 100
        ";
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // NOTE: most a nézet neve legyen 'list', mert a file az app/Apps/People/Views/list.php
        return View::render('list', [
            'title'  => 'Személyek – Lista',
            'people' => $rows,
        ]);
    }
}
