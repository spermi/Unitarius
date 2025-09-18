<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\DB;
use Core\View;

final class HomeController
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

        return View::render('home', [
            'title' => 'Unitarius – Kezdőlap',
            'rows'  => $rows,
        ]);
    }
}
