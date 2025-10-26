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
        $relations = [];

        try {
            $pdo = DB::pdo();
            $family  = $this->families->findByUuid($uuid);
            $relations = $pdo->query('SELECT code, label_hu FROM relation_type ORDER BY id ASC')
                            ->fetchAll(\PDO::FETCH_ASSOC);

            if ($family) {
                $stmt = $pdo->prepare('
                    SELECT fm.*, rt.label_hu AS relation_label
                    FROM family_members fm
                    LEFT JOIN relation_type rt ON rt.code = fm.relation_code
                    WHERE fm.family_uuid = :uuid
                    ORDER BY fm.created_at ASC
                ');
                $stmt->execute([':uuid' => $uuid]);
                $members = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Throwable $e) {
            error_log('[FamilyController::show] ' . $e->getMessage());
        }

        if (!$family) {
            http_response_code(404);
            return View::render('errors/404', [
                'title'   => 'Család nem található',
                'message' => 'A kért család nem létezik vagy törölve lett.',
            ]);
        }

        return View::render('family_detail', [
            'title'      => $family['family_name'] ?? 'Család',
            'family'     => $family,
            'members'    => $members,
            'pastor'     => $pastor,
            'relations'  => $relations,
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

        $name           = trim($_POST['name'] ?? '');
        $relation_code  = trim($_POST['relation_code'] ?? ''); // ✅ már helyes név
        $birth_date     = trim($_POST['birth_date'] ?? '');
        $death_date     = trim($_POST['death_date'] ?? '');
        $family_uuid    = trim($_POST['family_uuid'] ?? '');
        $parent_uuid    = trim($_POST['parent_uuid'] ?? '');
        //$is_primary     = isset($_POST['is_primary']) ? 1 : 0;

        $ok = false;

        if ($name !== '' && $family_uuid !== '') {
            try {
                $pdo = \Core\DB::pdo();
                $st = $pdo->prepare('
                    INSERT INTO family_members
                        (family_uuid, name, relation_code, birth_date, death_date, parent_uuid, created_at, updated_at)
                    VALUES
                        (:family_uuid, :name, :relation_code, :birth_date, :death_date, :parent_uuid, NOW(), NOW())

                ');

                $ok = $st->execute([
                    ':family_uuid'   => $family_uuid,
                    ':name'          => $name,
                    ':relation_code' => $relation_code ?: null,
                    ':birth_date'    => $birth_date ?: null,
                    ':death_date'    => $death_date ?: null,
                    ':parent_uuid'   => $parent_uuid ?: null,
                    //':is_primary'    => $is_primary,
                ]);
            } catch (\Throwable $e) {
                error_log('[FamilyController::saveMember] ' . $e->getMessage());
                die('<pre><b>Mentési hiba:</b> ' . htmlspecialchars($e->getMessage()) . '</pre>');
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
    // GET /adatlap/family/create ---- Old create new family form
    // ---------------------------------------------------------
    public function create(): string
    {
        require_can('adatlap.family.create');

        return View::render('family_form', [
            'title' => 'Új család hozzáadása',
        ]);
    }

    // ---------------------------------------------------------
    // POST /adatlap/family/store Create new family
    // ---------------------------------------------------------
        /**
     * Store new family (pastor self-create)
     */
  public function store(): void
{
    require_can('adatlap.family.create');

    if (!verify_csrf()) {
        flash_set('error', 'Érvénytelen vagy hiányzó biztonsági token.');
        header('Location: ' . base_url('/adatlap/family/create'));
        exit;
    }

    // --- Aktuális user lekérése ---
    $currentUser = \current_user();
    $userUuid = $currentUser['uuid'] ?? null;

    if (!$userUuid) {
        flash_set('error', 'Nem található a bejelentkezett felhasználó azonosítója.');
        header('Location: ' . base_url('/adatlap/family/create'));
        exit;
    }

    $name = trim($_POST['family_name'] ?? '');
    if ($name === '') {
        flash_set('error', 'A családnév megadása kötelező.');
        header('Location: ' . base_url('/adatlap/family/create'));
        exit;
    }

    try {
        $pdo = DB::pdo();
        $pdo->beginTransaction();

        // --- Kapcsolódó pastor UUID keresése ---
        $stmt = $pdo->prepare("SELECT uuid FROM pastors WHERE user_uuid = :user_uuid LIMIT 1");
        $stmt->execute([':user_uuid' => $userUuid]);
        $pastorUuid = $stmt->fetchColumn();

        if (!$pastorUuid) {
            throw new \Exception('Nincs a felhasználóhoz tartozó lelkész rekord.');
        }

        // --- Új család beszúrása ---
        $stmt = $pdo->prepare("
            INSERT INTO families (uuid, family_name, created_by_uuid, updated_by_uuid, created_at, updated_at)
            VALUES (gen_random_uuid(), :name, :created_by_uuid, :updated_by_uuid, NOW(), NOW())
            RETURNING uuid
        ");
        $stmt->execute([
            ':name'            => $name,
            ':created_by_uuid' => $userUuid,
            ':updated_by_uuid' => $userUuid,
        ]);
        $familyUuid = $stmt->fetchColumn();

        // --- Ellenőrzés: van-e már aktív családja ennek a lelkésznek ---
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM pastor_relationships
            WHERE pastor_uuid = :pastor_uuid AND is_current = TRUE
        ");
        $check->execute([':pastor_uuid' => $pastorUuid]);
        $hasCurrent = (int)$check->fetchColumn() > 0;

        // --- Kapcsolat beszúrása ---
        $rel = $pdo->prepare("
            INSERT INTO pastor_relationships (uuid, pastor_uuid, family_uuid, is_current)
            VALUES (gen_random_uuid(), :pastor_uuid, :family_uuid, :is_current)
        ");
       $isCurrent = $hasCurrent ? false : true;

        $rel->bindValue(':pastor_uuid', $pastorUuid);
        $rel->bindValue(':family_uuid', $familyUuid);
        $rel->bindValue(':is_current', $isCurrent, \PDO::PARAM_BOOL);
        $rel->execute();

        
        $pdo->commit();
        flash_set('success', 'A család sikeresen létrehozva.');
    } catch (\Throwable $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        flash_set('error', 'Hiba történt a mentés közben: ' . $e->getMessage());
    }

    header('Location: ' . base_url('/adatlap/family'));
    exit;
}


}
