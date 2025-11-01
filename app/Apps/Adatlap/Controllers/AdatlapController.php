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

    public function index(): string
    {
        require_can('adatlap.lelkesz');

        $user = \current_user() ?: [];
        $userUuid = $user['uuid'] ?? null;

        if (!$userUuid) {
            http_response_code(403);
            return View::render('errors/403', [
                'title'   => 'Hozzáférés megtagadva',
                'message' => 'Hiányzó felhasználói azonosító (uuid).'
            ]);
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
            return View::render('pastor_profile', [
                'title' => 'Lelkész adatlap',
            ]);

        } catch (\Throwable $e) {
            error_log('[AdatlapController::index] ' . $e->getMessage());
            http_response_code(500);
            return View::render('errors/500', [
                'title'   => 'Hiba történt',
                'message' => 'Nem sikerült betölteni az adatlapot.'
            ]);
        }
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
    // GET /adatlap/pastor/{uuid}/edit
    // Show form to edit own pastor profile
    // ---------------------------------------------------------
    public function editPastorProfile(array $params): string
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
            return View::render('errors/403', ['title' => 'Hozzáférés megtagadva', 'message' => 'Csak a saját adatlapod szerkeszthető.']);
        }

        return View::render('pastor_form', [
            'title'  => 'Lelkész adatlap szerkesztése',
            'pastor' => $own,
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

            $ins = $pdo->prepare("\                INSERT INTO pastor_education
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

    // ---------------------------------------------------------
    // POST /adatlap/pastor/{uuid}/update
    // Update own pastor profile
    // ---------------------------------------------------------
    public function updatePastorProfile(array $params): void
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
            flash_set('error', 'Csak a saját lelkész adatlapodat szerkesztheted.');
            header('Location: ' . base_url('/adatlap'));
            exit;
        }

        // Collect fields
        $firstName       = trim($_POST['first_name'] ?? '');
        $lastName        = trim($_POST['last_name'] ?? '');
        $birthName       = trim($_POST['birth_name'] ?? '');
        $birthDate       = trim($_POST['birth_date'] ?? '');
        $birthPlace      = trim($_POST['birth_place'] ?? '');
        $ordinationDate  = trim($_POST['ordination_date'] ?? '');
        $ordinationPlace = trim($_POST['ordination_place'] ?? '');
        $notes           = trim($_POST['notes'] ?? '');

        // Basic validation
        if (empty($firstName) || empty($lastName)) {
            flash_set('error', 'A vezetéknév és keresztnév mezők kitöltése kötelező.');
            header('Location: ' . base_url('/adatlap/pastor/' . $targetUuid . '/edit'));
            exit;
        }

        try {
            $pdo->beginTransaction();

            $upd = $pdo->prepare("
                UPDATE pastors SET
                    first_name = :fn,
                    last_name = :ln,
                    birth_name = :bn,
                    birth_date = :bd,
                    birth_place = :bp,
                    ordination_date = :od,
                    ordination_place = :op,
                    notes = :n,
                    updated_at = NOW()
                WHERE uuid = :u
            ");
            $upd->execute([
                ':fn' => $firstName,
                ':ln' => $lastName,
                ':bn' => $birthName !== '' ? $birthName : null,
                ':bd' => $birthDate !== '' ? $birthDate : null,
                ':bp' => $birthPlace !== '' ? $birthPlace : null,
                ':od' => $ordinationDate !== '' ? $ordinationDate : null,
                ':op' => $ordinationPlace !== '' ? $ordinationPlace : null,
                ':n'  => $notes !== '' ? $notes : null,
                ':u'  => $targetUuid,
            ]);

            $pdo->commit();
            flash_set('success', 'Adatok sikeresen frissítve.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[AdatlapController::updatePastorProfile][ERR] ' . $e->getMessage());
            flash_set('error', 'Hiba történt az adatok frissítése közben.');
        }

        header('Location: ' . base_url('/adatlap/pastor/' . $targetUuid));
        exit;
    }

}