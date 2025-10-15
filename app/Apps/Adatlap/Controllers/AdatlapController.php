<?php
declare(strict_types=1);

namespace App\Apps\Adatlap\Controllers;

use Core\View;
use Core\DB;

final class AdatlapController
{
    // ---------------------------------------------------------
    // GET /adatlap/studies
    // ---------------------------------------------------------
    public function studies(): string
    {
        // Példaadatok betöltése (később DB-ből)
        $userId = $_SESSION['user']['id'] ?? 0;

        $studies = [];
        if ($userId > 0) {
            try {
                $pdo = DB::pdo();
                $stmt = $pdo->prepare('SELECT * FROM studies WHERE user_id = :uid LIMIT 1');
                $stmt->execute([':uid' => $userId]);
                $studies = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                error_log('[AdatlapController::studies] ' . $e->getMessage());
            }
        }

        return View::render('studies', [
            'title'   => 'Tanulmányok',
            'studies' => $studies,
        ]);
    }
}
