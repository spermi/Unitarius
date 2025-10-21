<?php
declare(strict_types=1);

namespace App\Apps\Adatlap\Controllers;

use Core\DB;
use Core\View;
use Core\Request;
use App\Apps\Adatlap\Models\Family;
use App\Apps\Adatlap\Models\FamilyMember;
use App\Apps\Adatlap\Models\Pastor;
use App\Apps\Adatlap\Models\PastorRelationship;

// ---------------------------------------------------------
// FamilyController
//
// Handles all "family" related pages under /adatlap/family
// ---------------------------------------------------------
final class FamilyController
{
    protected Family $families;
    protected FamilyMember $members;
    protected Pastor $pastors;
    protected PastorRelationship $relationships;

    public function __construct()
    {
        $this->families       = new Family();
        $this->members        = new FamilyMember();
        $this->pastors        = new Pastor();
        $this->relationships  = new PastorRelationship();
    }

    // ---------------------------------------------------------
    // GET /adatlap/family
    // ---------------------------------------------------------
    public function index(): string
    {
        $families = [];
        $user = current_user();

        try {
            // Ha van jogosultsága minden családhoz
            if (can('adatlap.family.manage')) {
                $families = $this->families->allFamilies();
            } else {
                // Csak a saját családját láthatja (created_by_uuid alapján)
                $userUuid = $user['uuid'] ?? '';
                if ($userUuid !== '') {
                    $pdo = DB::pdo();
                    $stmt = $pdo->prepare('
                        SELECT * 
                        FROM families 
                        WHERE created_by_uuid = :uuid
                        ORDER BY created_at DESC
                    ');
                    $stmt->execute([':uuid' => $userUuid]);
                    $families = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                }
            }
        } catch (\Throwable $e) {
            error_log('[FamilyController::index] ' . $e->getMessage());
            $families = [];
        }

        return View::render('family_list', [
            'title'    => 'Családok',
            'families' => $families,
        ]);
    }

    // ---------------------------------------------------------
    // GET /adatlap/family/{uuid}
    // ---------------------------------------------------------
    public function show(array $params): string
    {
        $uuid = $params['uuid'] ?? '';

        if ($uuid === '') {
            http_response_code(400);
            return View::render('errors/400', [
                'title'   => 'Érvénytelen hivatkozás',
                'message' => 'Hiányzó családazonosító (UUID).',
            ]);
        }

        $family = null;
        $members = [];
        $pastor = null;

        try {
            $family  = $this->families->findByUuid($uuid);
            if ($family) {
                $members = $this->families->members($uuid);
                $pastor  = $this->pastors->where('family_uuid', $uuid)->first();
            }
        } catch (\Throwable) {
            $family = null;
        }

        if (!$family) {
            http_response_code(404);
            return View::render('errors/404', [
                'title'   => 'Család nem található',
                'message' => 'A kért család nem létezik vagy törölve lett.',
            ], null);
        }

        return View::render('family_detail', [
            'title'   => $family['family_name'] ?? 'Család',
            'family'  => $family,
            'members' => $members,
            'pastor'  => $pastor,
        ]);
    }

    // ---------------------------------------------------------
    // POST /adatlap/family/member/save
    // ---------------------------------------------------------
    public function saveMember(): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo \Core\View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ]);
            exit;
        }

        $name       = trim($_POST['name'] ?? '');
        $relation   = trim($_POST['relation'] ?? '');
        $birth_date = trim($_POST['birth_date'] ?? '');
        $death_date = trim($_POST['death_date'] ?? '');
        $family_uuid = trim($_POST['family_uuid'] ?? '');
        $parent_uuid = trim($_POST['parent_uuid'] ?? '');
        $is_primary  = isset($_POST['is_primary']) ? 1 : 0;

        $ok = false;

        if ($name !== '' && $family_uuid !== '') {
            try {
                $pdo = DB::pdo();
                $st = $pdo->prepare('
                    INSERT INTO family_members
                        (family_uuid, name, relation, birth_date, death_date, parent_uuid, is_primary, created_at, updated_at)
                    VALUES
                        (:family_uuid, :name, :relation, :birth_date, :death_date, :parent_uuid, :is_primary, NOW(), NOW())
                ');

                $ok = $st->execute([
                    ':family_uuid' => $family_uuid,
                    ':name'        => $name,
                    ':relation'    => $relation,
                    ':birth_date'  => $birth_date ?: null,
                    ':death_date'  => $death_date ?: null,
                    ':parent_uuid' => $parent_uuid ?: null,
                    ':is_primary'  => $is_primary,
                ]);
            } catch (\Throwable $e) {
                    error_log('[FamilyController::saveMember] ' . $e->getMessage());
                    if (function_exists('flash_set')) {
                        flash_set('error', 'Hiba történt a mentés közben: ' . $e->getMessage());
                    }
                }
        }

        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error',
                $ok ? 'Családtag sikeresen hozzáadva.' : 'Nem sikerült menteni a családtagot.'
            );
        }

        header('Location: ' . base_url('/adatlap/family/' . $family_uuid));
        exit;
    }

    // ---------------------------------------------------------
    // GET /adatlap/family/tree/{uuid}
    // ---------------------------------------------------------
    public function tree(array $params): void
    {
        // --- Jogosultság ellenőrzése ---
        require_can('adatlap.family.view');

        header('Content-Type: application/json; charset=utf-8');

        $familyUuid = $params['uuid'] ?? '';

        if ($familyUuid === '') {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Hiányzó család UUID.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            // --- Elsődleges családtag lekérése ---
            $rootMember = $this->members
                ->where('family_uuid', $familyUuid)
                ->where('is_primary', true)
                ->first();

            if (!$rootMember) {
                http_response_code(404);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Nincs elsődleges családtag megadva.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // --- Családfa felépítése ---
            $tree   = $this->members->buildTree($rootMember['uuid']);
            $family = $this->families->findByUuid($familyUuid);

            // --- JSON válasz ---
            echo json_encode([
                'status'       => 'success',
                'family_name'  => $family['family_name'] ?? '',
                'root_member'  => $tree,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            error_log('[FamilyController::tree] ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Hiba történt a családfa generálásakor.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    // ---------------------------------------------------------
    // GET /adatlap/family/create
    // ---------------------------------------------------------
    public function create(): string
    {
        require_can('adatlap.family.add');

        return View::render('family_form', [
            'title' => 'Új család hozzáadása',
        ]);
    }

    // ---------------------------------------------------------
    // POST /adatlap/family/store
    // ---------------------------------------------------------
    public function store(): void
    {
        require_can('adatlap.family.add');

        if (!verify_csrf()) {
            flash_set('error', 'Érvénytelen biztonsági token.');
            header('Location: ' . base_url('/adatlap/family/create'));
            exit;
        }

        $name = trim($_POST['family_name'] ?? '');

        if ($name === '') {
            flash_set('error', 'A család neve kötelező.');
            header('Location: ' . base_url('/adatlap/family/create'));
            exit;
        }

        try {
            db_insert('families', [
                'family_name' => $name,
                // db_insert fills created_at, updated_at, created_by_uuid, updated_by_uuid
            ]);
            flash_set('success', 'Család sikeresen hozzáadva.');
        } catch (\Throwable $e) {
            flash_set('error', 'Hiba történt a mentés közben: ' . $e->getMessage());
        }

        header('Location: ' . base_url('/adatlap/family'));
        exit;
    }
}
