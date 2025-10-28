<?php
declare(strict_types=1);

namespace App\Apps\Adatlap\Controllers;

use Core\View;
use Core\DB;

final class AdatlapController
{


    // ---------------------------------------------------------
    // GET /adatlap
    // Redirects to own pastor profile if exists, else to legacy Studies page.
    // ---------------------------------------------------------

    public function index(): void
    {
        require_can('adatlap.lelkesz');

        $user = \current_user() ?: [];
        $userUuid = $user['uuid'] ?? null;

        if (!$userUuid) {
            http_response_code(403);
            echo View::render('errors/403', [
                'title'   => 'Hozzáférés megtagadva',
                'message' => 'Hiányzó felhasználói azonosító (uuid).'
            ]);
            return;
        }

        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("SELECT uuid FROM pastors WHERE user_uuid = :u LIMIT 1");
            $stmt->execute([':u' => $userUuid]);
            $pastorUuid = $stmt->fetchColumn();

            if ($pastorUuid) {
                header('Location: ' . base_url('/adatlap/pastor/' . $pastorUuid));
                exit;
            }

            // NINCS pastor rekord → mutassunk információs oldalt (nem 404)
            echo View::render('pastor_profile', [
                'title' => 'Lelkész adatlap',
            ]);
            return;

        } catch (\Throwable $e) {
            error_log('[AdatlapController::index] ' . $e->getMessage());
            http_response_code(500);
            echo View::render('errors/500', [
                'title'   => 'Hiba történt',
                'message' => 'Nem sikerült betölteni az adatlapot.'
            ]);
            return;
        }
    }




    // ---------------------------------------------------------
    // GET /adatlap/studies
    // single pastor's studies view , in a list 
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


    // ---------------------------------------------------------
    // POST /adatlap/studies/save
    //* Megjegyzés: átmeneti megoldás, később pastor_education (pastor_uuid) felé migráljuk.
    // ---------------------------------------------------------
    public function saveStudies(): void
    {
        require_can('adatlap.lelkesz');

        if (!verify_csrf()) {
            flash_set('error', 'Érvénytelen vagy hiányzó biztonsági token.');
            header('Location: ' . base_url('/adatlap/studies'));
            exit;
        }

        // Current session user context; summary is kept per-user
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            flash_set('error', 'Hiányzó felhasználó azonosító.');
            header('Location: ' . base_url('/adatlap/studies'));
            exit;
        }

        // Collect and trim fields (all optional; empty -> NULL)
        $fields = [
            'erettsegi_ev','erettsegi_intezmeny',
            'teologia_intezmeny','kezdes_ev','vegzes_ev',
            'szakvizsga_ev','licenc_ev','magiszter_ev',
            'kepesito_ev','felszent_ev','felszent_hely',
        ];
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }

        try {
            $pdo = DB::pdo();
            $pdo->beginTransaction();

            // Upsert: ensure exactly one row per user_id
            $sql = "
                INSERT INTO studies
                (user_id, erettsegi_ev, erettsegi_intezmeny, teologia_intezmeny, kezdes_ev, vegzes_ev,
                szakvizsga_ev, licenc_ev, magiszter_ev, kepesito_ev, felszent_ev, felszent_hely, created_at, updated_at)
                VALUES
                (:user_id, :erettsegi_ev, :erettsegi_intezmeny, :teologia_intezmeny, :kezdes_ev, :vegzes_ev,
                :szakvizsga_ev, :licenc_ev, :magiszter_ev, :kepesito_ev, :felszent_ev, :felszent_hely, NOW(), NOW())
                ON CONFLICT (user_id) DO UPDATE SET
                erettsegi_ev        = EXCLUDED.erettsegi_ev,
                erettsegi_intezmeny = EXCLUDED.erettsegi_intezmeny,
                teologia_intezmeny  = EXCLUDED.teologia_intezmeny,
                kezdes_ev           = EXCLUDED.kezdes_ev,
                vegzes_ev           = EXCLUDED.vegzes_ev,
                szakvizsga_ev       = EXCLUDED.szakvizsga_ev,
                licenc_ev           = EXCLUDED.licenc_ev,
                magiszter_ev        = EXCLUDED.magiszter_ev,
                kepesito_ev         = EXCLUDED.kepesito_ev,
                felszent_ev         = EXCLUDED.felszent_ev,
                felszent_hely       = EXCLUDED.felszent_hely,
                updated_at          = NOW()
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id'               => $userId,
                ':erettsegi_ev'          => $data['erettsegi_ev'] ?: null,
                ':erettsegi_intezmeny'   => $data['erettsegi_intezmeny'] ?: null,
                ':teologia_intezmeny'    => $data['teologia_intezmeny'] ?: null,
                ':kezdes_ev'             => $data['kezdes_ev'] ?: null,
                ':vegzes_ev'             => $data['vegzes_ev'] ?: null,
                ':szakvizsga_ev'         => $data['szakvizsga_ev'] ?: null,
                ':licenc_ev'             => $data['licenc_ev'] ?: null,
                ':magiszter_ev'          => $data['magiszter_ev'] ?: null,
                ':kepesito_ev'           => $data['kepesito_ev'] ?: null,
                ':felszent_ev'           => $data['felszent_ev'] ?: null,
                ':felszent_hely'         => $data['felszent_hely'] ?: null,
            ]);

            $pdo->commit();
            flash_set('success', 'Tanulmányok sikeresen mentve.');
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[AdatlapController::saveStudies][ERR] ' . $e->getMessage());
            flash_set('error', 'Hiba történt a mentés közben.');
        }

        header('Location: ' . base_url('/adatlap/studies'));
        exit;
    }

    // ---------------------------------------------------------
    // GET /adatlap/pastor/{uuid}
    // Show own pastor profile + education list (ordered by dates)
    // ---------------------------------------------------------
    public function pastorProfile(array $params): string
    {
        require_can('adatlap.lelkesz');

        $targetUuid = $params['uuid'] ?? '';
        if ($targetUuid === '') {
            http_response_code(400);
            return View::render('errors/400', ['title' => 'Hiányzó UUID', 'message' => 'Hiányzó lelkész azonosító.']);
        }

        // Only own profile allowed
        $currentUser = \current_user();
        $userUuid = $currentUser['uuid'] ?? null;
        if (!$userUuid) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Hozzáférés megtagadva', 'message' => 'Hiányzó felhasználó.']);
        }

        $pdo = DB::pdo();

        // Resolve current user's pastor UUID
        $stmt = $pdo->prepare("SELECT uuid, first_name, last_name, birth_name, birth_date, birth_place, ordination_date, ordination_place, notes
                            FROM pastors WHERE user_uuid = :u LIMIT 1");
        $stmt->execute([':u' => $userUuid]);
        $own = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        if (!$own || (string)$own['uuid'] !== $targetUuid) {
            http_response_code(403);
            return View::render('errors/403', ['title' => 'Hozzáférés megtagadva', 'message' => 'Csak a saját adatlapod érhető el.']);
        }

        // Load education rows
        $ed = $pdo->prepare("SELECT * FROM pastor_education WHERE pastor_uuid = :p ORDER BY start_date DESC NULLS LAST, end_date DESC NULLS LAST, created_at DESC");
        $ed->execute([':p' => $targetUuid]);
        $education = $ed->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return View::render('pastor_profile', [
            'title'     => 'Lelkész adatlap',
            'pastor'    => $own,
            'education' => $education,
        ]);
    }

    // ---------------------------------------------------------
    // POST /adatlap/pastor/{uuid}/education/store
    // Insert a new education row for own pastor UUID
    // ---------------------------------------------------------
    public function storeEducation(array $params): void
    {
        require_can('adatlap.lelkesz');

        if (!verify_csrf()) {
            flash_set('error', 'Érvénytelen vagy hiányzó biztonsági token.');
            header('Location: ' . base_url('/adatlap'));
            exit;
        }

        $targetUuid = $params['uuid'] ?? '';
        if ($targetUuid === '') {
            flash_set('error', 'Hiányzó lelkész azonosító.');
            header('Location: ' . base_url('/adatlap'));
            exit;
        }

        $currentUser = \current_user();
        $userUuid = $currentUser['uuid'] ?? null;

        // Check ownership (user -> pastors.uuid)
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT uuid FROM pastors WHERE user_uuid = :u LIMIT 1");
        $stmt->execute([':u' => $userUuid]);
        $ownPastorUuid = $stmt->fetchColumn();

        if (!$ownPastorUuid || (string)$ownPastorUuid !== $targetUuid) {
            flash_set('error', 'Csak a saját lelkész adatlapodhoz adhatsz tanulmányt.');
            header('Location: ' . base_url('/adatlap'));
            exit;
        }

        // Collect fields
        $institution    = trim($_POST['institution'] ?? '');
        $fieldOfStudy   = trim($_POST['field_of_study'] ?? '');
        $degree         = trim($_POST['degree'] ?? '');
        $startDate      = trim($_POST['start_date'] ?? '');
        $endDate        = trim($_POST['end_date'] ?? '');
        $note           = trim($_POST['note'] ?? '');

        try {
            $pdo->beginTransaction();

            $ins = $pdo->prepare("
                INSERT INTO pastor_education
                (uuid, pastor_uuid, institution, field_of_study, degree, start_date, end_date, note, created_at, updated_at)
                VALUES
                (gen_random_uuid(), :p, :i, :f, :d, :sd, :ed, :n, NOW(), NOW())
            ");
            $ins->execute([
                ':p'  => $targetUuid,
                ':i'  => $institution !== ''    ? $institution    : null,
                ':f'  => $fieldOfStudy !== ''   ? $fieldOfStudy   : null,
                ':d'  => $degree !== ''         ? $degree         : null,
                ':sd' => $startDate !== ''      ? $startDate      : null,
                ':ed' => $endDate !== ''        ? $endDate        : null,
                ':n'  => $note !== ''           ? $note           : null,
            ]);

            $pdo->commit();
            flash_set('success', 'Tanulmány hozzáadva.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[AdatlapController::storeEducation][ERR] ' . $e->getMessage());
            flash_set('error', 'Hiba történt a mentés közben.');
        }

        header('Location: ' . base_url('/adatlap/pastor/' . $targetUuid));
        exit;
    }



}
