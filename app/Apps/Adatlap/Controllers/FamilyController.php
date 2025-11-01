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
    $user = current_user();
    $families = [];

    // --- DEBUG SWITCH  ---
    $DBG = false;

    try {
        $canManage = can('adatlap.family.manage');
        if ($DBG) {
            error_log("[FamilyController::index][DBG] user_uuid=" . ($user['uuid'] ?? 'NULL') . " canManage=" . ($canManage ? '1' : '0'));
        }

        if ($canManage) {
            // Admin: unchanged (see everything)
            $families = $this->families->allFamilies();
            if ($DBG) {
                error_log("[FamilyController::index][DBG] ADMIN branch -> families(all) count=" . count($families));
            }
        } else {
            $userUuid = $user['uuid'] ?? '';
            if ($userUuid !== '') {
                // Find pastor row for the current user
                $pastor = $this->pastors->findByUser($userUuid); // ['uuid' => ...] or null
                $pastorUuid = $pastor['uuid'] ?? null;

                if ($DBG) {
                    error_log("[FamilyController::index][DBG] resolved pastor_uuid=" . ($pastorUuid ?: 'NULL') . " for user_uuid=" . $userUuid);
                }

                if ($pastorUuid) {
                    $pdo = DB::pdo();

                    // Extra: count PR rows for this pastor (diagnostics)
                    $cnt = $pdo->prepare('SELECT COUNT(*) FROM pastor_relationships WHERE pastor_uuid = :p');
                    $cnt->execute([':p' => $pastorUuid]);
                    $prCount = (int)$cnt->fetchColumn();
                    if ($DBG) {
                        error_log("[FamilyController::index][DBG] PR rows for pastor_uuid={$pastorUuid}: {$prCount}");
                    }

                    // Only families linked to THIS pastor via PR
                    $stmt = $pdo->prepare('
                        SELECT 
                            f.*, 
                            pr.marriage_date, 
                            pr.marriage_place, 
                            pr.marriage_end_date,
                            pr.is_current
                        FROM pastor_relationships pr
                        JOIN families f ON f.uuid = pr.family_uuid
                        WHERE pr.pastor_uuid = :pastor_uuid
                        ORDER BY pr.marriage_date DESC
                    ');
                    $stmt->execute([':pastor_uuid' => $pastorUuid]);
                    $families = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

                    if ($DBG) {
                        error_log("[FamilyController::index][DBG] filtered families for pastor_uuid={$pastorUuid}: " . count($families));
                        // opcionális: felsoroljuk az UUID-kat
                        $uuids = array_map(fn($r) => $r['uuid'] ?? 'NULL', $families);
                        error_log("[FamilyController::index][DBG] family_uuids=[" . implode(',', $uuids) . "]");
                    }
                } else {
                    // Not a pastor: no families visible
                    $families = [];
                    if ($DBG) {
                        error_log("[FamilyController::index][DBG] user is not linked to pastors -> families count=0");
                    }
                }
            } else {
                if ($DBG) error_log("[FamilyController::index][DBG] userUuid EMPTY");
            }
        }
    } catch (\Throwable $e) {
        error_log('[FamilyController::index][ERR] ' . $e->getMessage());
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
        $relations = [];
        $relationship = [];

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

                // Fetch relationship data for the current pastor
                $user = current_user();
                $pastor = $this->pastors->findByUser($user['uuid'] ?? '');
                if ($pastor) {
                    $rel_stmt = $pdo->prepare('SELECT * FROM pastor_relationships WHERE family_uuid = :family_uuid AND pastor_uuid = :pastor_uuid');
                    $rel_stmt->execute([':family_uuid' => $uuid, ':pastor_uuid' => $pastor['uuid']]);
                    $relationship = $rel_stmt->fetch() ?: null;
                }
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
            'title'        => $family['family_name'] ?? 'Család',
            'family'       => $family,
            'members'      => $members,
            'relationship' => $relationship,
            'relations'    => $relations,
        ]);
    }


    // ---------------------------------------------------------
    // POST /adatlap/family/member/save
    // ---------------------------------------------------------
    public function saveMember(): void
    {
        require_can('adatlap.lelkesz');

        if (!verify_csrf()) {
            flash_set('error', 'Érvénytelen vagy hiányzó biztonsági token.');
            header('Location: ' . base_url('/adatlap/family'));
            exit;
        }

        $requestData = [
            'family_uuid'        => trim($_POST['family_uuid'] ?? ''),
            'member_uuid'        => trim($_POST['member_uuid'] ?? ''),
            'relation_code'      => trim($_POST['relation_code'] ?? ''),
            'name'               => trim($_POST['name'] ?? ''),
            'birth_date'         => trim($_POST['birth_date'] ?? ''),
            'death_date'         => trim($_POST['death_date'] ?? ''),
            'spouse_pastor_uuid' => trim($_POST['spouse_pastor_uuid'] ?? ''),
            'gender'             => strtolower(trim($_POST['gender'] ?? '')),
        ];

        if ($requestData['gender'] !== 'male' && $requestData['gender'] !== 'female') {
            $requestData['gender'] = null;
        }

        $redirectUrl = base_url('/adatlap/family/' . $requestData['family_uuid']);

        if ($requestData['family_uuid'] === '') {
            flash_set('error', 'Hiányzó család azonosító.');
            header('Location: ' . base_url('/adatlap/family'));
            exit;
        }

        try {
            if ($requestData['member_uuid'] !== '') {
                $this->updateMember($requestData, $redirectUrl);
            } else {
                $this->createMember($requestData, $redirectUrl);
            }
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash_set('error', 'Hiba történt a mentés közben: ' . $e->getMessage());
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    private function updateMember(array $data, string $redirectUrl): void
    {
        $currentUser = current_user();
        $userUuid = $currentUser['uuid'] ?? null;

        if ($data['relation_code'] === '' || $data['name'] === '') {
            flash_set('error', 'A név és a kapcsolat típus megadása kötelező.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $pdo = DB::pdo();
        $pdo->beginTransaction();

        // Permission check: can the current user edit this member?
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM family_members fm
            JOIN pastor_relationships pr ON fm.family_uuid = pr.family_uuid
            JOIN pastors p ON pr.pastor_uuid = p.uuid
            WHERE fm.uuid = :member_uuid AND p.user_uuid = :user_uuid
        ");
        $stmt->execute([':member_uuid' => $data['member_uuid'], ':user_uuid' => $userUuid]);

        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->rollBack();
            flash_set('error', 'Nincs jogosultságod a tag szerkesztéséhez.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $sql = "
            UPDATE family_members SET
                name = :name,
                relation_code = :relation_code,
                birth_date = :birth_date,
                death_date = :death_date,
                gender = :gender,
                updated_at = NOW()
            WHERE uuid = :member_uuid
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':relation_code' => $data['relation_code'],
            ':birth_date' => $data['birth_date'] ?: null,
            ':death_date' => $data['death_date'] ?: null,
            ':gender' => $data['gender'],
            ':member_uuid' => $data['member_uuid']
        ]);

        $pdo->commit();
        flash_set('success', 'Családtag sikeresen frissítve.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    private function createMember(array $data, string $redirectUrl): void
    {
        $currentUser = current_user();
        $userUuid = $currentUser['uuid'] ?? null;

        if ($data['relation_code'] === '') {
            flash_set('error', 'Hiányzó kapcsolat típus.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $pdo = DB::pdo();
        $pdo->beginTransaction();

        //---------------------------------------------------------
        // 0) permission: family belongs to current pastor?
        //---------------------------------------------------------
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM pastor_relationships
            WHERE pastor_uuid = (SELECT uuid FROM pastors WHERE user_uuid = :user_uuid)
            AND family_uuid  = :family_uuid
        ");
        $stmt->execute([':user_uuid' => $userUuid, ':family_uuid' => $data['family_uuid']]);
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->rollBack();
            flash_set('error', 'Nem adhatsz hozzá tagot ehhez a családhoz.');
            header('Location: ' . base_url('/adatlap/family'));
            exit;
        }

        //---------------------------------------------------------
        // 1) normalize relation code (accept HU label too)
        //---------------------------------------------------------
        $stmt = $pdo->prepare("
            SELECT
                code,
                (code IN ('husband','wife'))   AS is_spouse,
                (code IN ('child','children')) AS is_child
            FROM relation_type
            WHERE LOWER(code) = LOWER(:v) OR label_hu ILIKE :v
            LIMIT 1
        ");
        $stmt->execute([':v' => $data['relation_code']]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            $pdo->rollBack();
            flash_set('error', 'Érvénytelen kapcsolat típus.');
            header('Location: ' . $redirectUrl);
            exit;
        }
        $relationCode = (string)$row['code'];
        $isSpouse     = !empty($row['is_spouse']);
        $isChild      = !empty($row['is_child']);

        //---------------------------------------------------------
        // 2) current pastor
        //---------------------------------------------------------
        $stmt = $pdo->prepare("SELECT uuid, first_name, last_name FROM pastors WHERE user_uuid = :u LIMIT 1");
        $stmt->execute([':u' => $userUuid]);
        $pastorRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        $currentPastorUuid = $pastorRow['uuid'] ?? null;
        $currentPastorName = trim(($pastorRow['last_name'] ?? '') . ' ' . ($pastorRow['first_name'] ?? ''));

        // * Helper: ensure pastor has a member row (husband|wife) in this family
        $ensurePastorMember = function() use ($pdo, $data, $userUuid, $currentPastorUuid, $currentPastorName): string {
            $q = $pdo->prepare("
                SELECT uuid FROM family_members
                WHERE family_uuid = :f
                AND (pastor_uuid = :p OR user_uuid = :u)
                AND relation_code IN ('husband','wife')
                LIMIT 1
            ");
            $q->execute([':f' => $data['family_uuid'], ':p' => $currentPastorUuid, ':u' => $userUuid]);
            $mid = $q->fetchColumn();
            if ($mid) {
                if ($currentPastorUuid) {
                    $u = $pdo->prepare("UPDATE family_members SET pastor_uuid = :p WHERE uuid = :m AND pastor_uuid IS NULL");
                    $u->execute([':p' => $currentPastorUuid, ':m' => $mid]);
                }
                return (string)$mid;
            }

            // choose role by current family state
            $q = $pdo->prepare("
                SELECT
                    SUM((relation_code='husband')::int) AS husbands,
                    SUM((relation_code='wife')::int)    AS wives
                FROM family_members
                WHERE family_uuid = :f
            ");
            $q->execute([':f' => $data['family_uuid']]);
            $c = $q->fetch(\PDO::FETCH_ASSOC) ?: ['husbands' => 0, 'wives' => 0];
            $role = ((int)$c['wives'] > 0 && (int)$c['husbands'] === 0) ? 'husband'
                : ((int)$c['husbands'] > 0 && (int)$c['wives'] === 0 ? 'wife' : 'husband');

            $roleGender = ($role === 'husband' ? 'male' : ($role === 'wife' ? 'female' : null));

            $q = $pdo->prepare("
                INSERT INTO family_members
                (uuid, family_uuid, user_uuid, pastor_uuid, parent_uuid, name, relation_code, gender, birth_date, death_date, created_at, updated_at)
                VALUES
                (gen_random_uuid(), :f, :u, :p, NULL, :n, :r, :rg, NULL, NULL, NOW(), NOW())
                RETURNING uuid
            ");
            $q->execute([
                ':f' => $data['family_uuid'],
                ':u' => $userUuid,
                ':p' => $currentPastorUuid,
                ':n' => $currentPastorName,
                ':r' => $role,
                ':rg' => $roleGender,
            ]);
            return (string)$q->fetchColumn();
        };

        // * Helper: read current husband/wife member uuids in this family
        $fetchSpouses = function() use ($pdo, $data): array {
            $h = $pdo->prepare("SELECT uuid FROM family_members WHERE family_uuid = :f AND relation_code = 'husband' LIMIT 1");
            $w = $pdo->prepare("SELECT uuid FROM family_members WHERE family_uuid = :f AND relation_code = 'wife'    LIMIT 1");
            $h->execute([':f' => $data['family_uuid']]);
            $w->execute([':f' => $data['family_uuid']]);
            return [
                'husband' => $h->fetchColumn() ?: null,
                'wife'    => $w->fetchColumn() ?: null,
            ];
        };

        //---------------------------------------------------------
        // 3) parent fallback for child + prefill father/mother for the NEW child (if spouses exist)
        //---------------------------------------------------------
        $parentUuid = null;
        $fatherUuid = null;
        $motherUuid = null;
        if ($isChild) {
            if (!$currentPastorUuid) {
                $pdo->rollBack();
                flash_set('error', 'Hiányzó pastor rekord a felhasználóhoz.');
                header('Location: ' . $redirectUrl);
                exit;
            }
            $parentUuid = $ensurePastorMember();

            $sp = $fetchSpouses();
            if (!empty($sp['husband'])) $fatherUuid = (string)$sp['husband'];
            if (!empty($sp['wife']))    $motherUuid = (string)$sp['wife'];
        }

        //---------------------------------------------------------
        // 4) insert new member (gender included; father/mother for child prefill)
        //---------------------------------------------------------
        $insertSql = "
            INSERT INTO family_members 
            (uuid, family_uuid, user_uuid, pastor_uuid, parent_uuid, father_uuid, mother_uuid, name, relation_code, gender, birth_date, death_date, created_at, updated_at)
            VALUES 
            (gen_random_uuid(), :family_uuid, NULL, :pastor_uuid, :parent_uuid, :father_uuid, :mother_uuid, :name, :relation_code, :gender, :birth_date, :death_date, NOW(), NOW())
            RETURNING uuid
        ";
        $stmt = $pdo->prepare($insertSql);
        $stmt->bindValue(':family_uuid', $data['family_uuid']);
        $stmt->bindValue(':pastor_uuid', ($isSpouse && $data['spouse_pastor_uuid'] !== '') ? $data['spouse_pastor_uuid'] : null);
        $stmt->bindValue(':parent_uuid', $parentUuid);
        $stmt->bindValue(':father_uuid', $fatherUuid);
        $stmt->bindValue(':mother_uuid', $motherUuid);
        $stmt->bindValue(':name', $data['name'] !== '' ? $data['name'] : null);
        $stmt->bindValue(':relation_code', $relationCode);
        $stmt->bindValue(':gender', $data['gender']);
        $stmt->bindValue(':birth_date', $data['birth_date'] !== '' ? $data['birth_date'] : null);
        $stmt->bindValue(':death_date', $data['death_date'] !== '' ? $data['death_date'] : null);
        $stmt->execute();
        $newMemberUuid = (string)$stmt->fetchColumn();

        //---------------------------------------------------------
        // 5) spouse case: ensure pastor member + PR upsert (no current hijack)
        //---------------------------------------------------------
        if ($isSpouse && $newMemberUuid) {
            $ensurePastorMember();

            $stmt = $pdo->prepare("SELECT uuid FROM pastors WHERE user_uuid = :u LIMIT 1");
            $stmt->execute([':u' => $userUuid]);
            $pastorUuid = $stmt->fetchColumn();

            if ($pastorUuid) {
                $cur = $pdo->prepare("
                    SELECT family_uuid FROM pastor_relationships
                    WHERE pastor_uuid = :p AND is_current = TRUE
                    LIMIT 1
                ");
                $cur->execute([':p' => $pastorUuid]);
                $currentFamilyForPastor = $cur->fetchColumn() ?: null;

                $makeCurrent = ($currentFamilyForPastor === null || $currentFamilyForPastor === $data['family_uuid']);
                $icVal = $makeCurrent ? 'true' : 'false';

                if ($data['spouse_pastor_uuid'] !== '') {
                    $u = $pdo->prepare("
                        UPDATE pastor_relationships
                        SET spouse_pastor_uuid = :sp,
                            spouse_uuid        = NULL,
                            is_current         = :ic::boolean
                        WHERE pastor_uuid = :p AND family_uuid = :f
                    ");
                    $u->execute([':sp' => $data['spouse_pastor_uuid'], ':ic' => $icVal, ':p' => $pastorUuid, ':f' => $data['family_uuid']]);

                    if ($u->rowCount() === 0) {
                        $i = $pdo->prepare("
                            INSERT INTO pastor_relationships
                            (uuid, pastor_uuid, family_uuid, spouse_pastor_uuid, is_current)
                            VALUES
                            (gen_random_uuid(), :p, :f, :sp, :ic::boolean)
                        ");
                        $i->execute([':p' => $pastorUuid, ':f' => $data['family_uuid'], ':sp' => $data['spouse_pastor_uuid'], ':ic' => $icVal]);
                    }
                } else {
                    $u = $pdo->prepare("
                        UPDATE pastor_relationships
                        SET spouse_uuid        = :sm,
                            spouse_pastor_uuid = NULL,
                            is_current         = :ic::boolean
                        WHERE pastor_uuid = :p AND family_uuid = :f
                    ");
                    $u->execute([':sm' => $newMemberUuid, ':ic' => $icVal, ':p' => $pastorUuid, ':f' => $data['family_uuid']]);

                    if ($u->rowCount() === 0) {
                        $i = $pdo->prepare("
                            INSERT INTO pastor_relationships
                            (uuid, pastor_uuid, family_uuid, spouse_uuid, is_current)
                            VALUES
                            (gen_random_uuid(), :p, :f, :sm, :ic::boolean)
                        ");
                        $i->execute([':p' => $pastorUuid, ':f' => $data['family_uuid'], ':sm' => $newMemberUuid, ':ic' => $icVal]);
                    }
                }
            }
        }

        //---------------------------------------------------------
        // 6) FAMILY-WIDE NORMALIZATION
        //---------------------------------------------------------
        $spouses = $fetchSpouses();
        if (!empty($spouses['husband'])) {
            $u = $pdo->prepare("
                UPDATE family_members 
                SET father_uuid = COALESCE(father_uuid, :father)
                WHERE family_uuid = :f
                AND LOWER(relation_code) IN ('child','children')
                AND father_uuid IS NULL
            ");
            $u->execute([':father' => $spouses['husband'], ':f' => $data['family_uuid']]);
        }
        if (!empty($spouses['wife'])) {
            $u = $pdo->prepare("
                UPDATE family_members
                SET mother_uuid = COALESCE(mother_uuid, :mother)
                WHERE family_uuid = :f
                AND LOWER(relation_code) IN ('child','children')
                AND mother_uuid IS NULL
            ");
            $u->execute([':mother' => $spouses['wife'], ':f' => $data['family_uuid']]);
        }

        $pdo->commit();
        flash_set('success', 'Családtag sikeresen hozzáadva és a gyermekek szülői mezői frissítve.');
        header('Location: ' . $redirectUrl);
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
            http_response_code( 00);
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

        // NEW: marriage fields
        $marriageDate  = trim($_POST['marriage_date'] ?? '');
        $marriagePlace = trim($_POST['marriage_place'] ?? '');

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
            // NOTE: start_date / end_date most kimarad; marriage_end_date NULL
            $rel = $pdo->prepare("
                INSERT INTO pastor_relationships
                (uuid, pastor_uuid, family_uuid, marriage_date, marriage_place, is_current)
                VALUES
                (gen_random_uuid(), :pastor_uuid, :family_uuid, :marriage_date, :marriage_place, :is_current)
            ");
            $isCurrent = $hasCurrent ? false : true;

            $rel->bindValue(':pastor_uuid',    $pastorUuid);
            $rel->bindValue(':family_uuid',    $familyUuid);
            $rel->bindValue(':marriage_date',  $marriageDate !== '' ? $marriageDate : null);
            $rel->bindValue(':marriage_place', $marriagePlace !== '' ? $marriagePlace : null);
            $rel->bindValue(':is_current',     $isCurrent, \PDO::PARAM_BOOL);
            $rel->execute();

            $pdo->commit();
            flash_set('success', 'A család sikeresen létrehozva.');
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Friendly error for unique violation on current pastor relationship
            if ($e instanceof \PDOException) {
                $sqlState  = $e->getCode();           // e.g. '23505'
                $errorInfo = $e->errorInfo ?? [];     // [sqlstate, drv_code, message]
                $pgMessage = $errorInfo[2] ?? $e->getMessage();

                if ($sqlState === '23505' && stripos($pgMessage, 'uidx_prel_current_per_pastor') !== false) {
                    flash_set('warning',
                        'Nem hozható létre új, „jelenlegi” házassági kapcsolat ehhez a lelkészhez, '
                        . 'mert már van egy aktív kapcsolat. '
                        . 'Zárd le a korábbit (vagy hagyd üresen a házasság dátumát a létrehozáskor), majd próbáld újra.'
                    );
                    header('Location: ' . base_url('/adatlap/family'));
                    exit;
                }
            }

            error_log('[FamilyController::store][ERR] ' . $e->getMessage());
            flash_set('error', 'Hiba történt a mentés közben.');
            header('Location: ' . base_url('/adatlap/family'));
            exit;
        }

        header('Location: ' . base_url('/adatlap/family'));
        exit;
    }


    // ---------------------------------------------------------
    // POST /adatlap/family/marriage/update
    // ---------------------------------------------------------
    public function updateMarriage(): void
    {
        require_can('adatlap.lelkesz');

        if (!verify_csrf()) {
            flash_set('error', 'Érvénytelen vagy hiányzó biztonsági token.');
            header('Location: ' . base_url('/adatlap/family'));
            exit;
        }

        $familyUuid       = trim($_POST['family_uuid'] ?? '');
        $pastorUuid       = trim($_POST['pastor_uuid'] ?? '');
        $marriageDate     = trim($_POST['marriage_date'] ?? '');
        $marriagePlace    = trim($_POST['marriage_place'] ?? '');
        $marriageEndDate  = trim($_POST['marriage_end_date'] ?? '');

        $redirectUrl = base_url('/adatlap/family/' . $familyUuid);

        if ($familyUuid === '' || $pastorUuid === '') {
            flash_set('error', 'Hiányzó család vagy lelkész azonosító.');
            header('Location: ' . base_url('/adatlap/family'));
            exit;
        }

        try {
            $pdo = DB::pdo();
            $pdo->beginTransaction();

            // Permission check: ensure the current user is the pastor associated with this relationship
            $currentUser = current_user();
            $userUuid = $currentUser['uuid'] ?? null;

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM pastor_relationships pr
                JOIN pastors p ON pr.pastor_uuid = p.uuid
                WHERE pr.family_uuid = :family_uuid
                AND pr.pastor_uuid = :pastor_uuid
                AND p.user_uuid = :user_uuid
            ");
            $stmt->execute([
                ':family_uuid' => $familyUuid,
                ':pastor_uuid' => $pastorUuid,
                ':user_uuid'   => $userUuid
            ]);

            if ((int)$stmt->fetchColumn() === 0) {
                $pdo->rollBack();
                flash_set('error', 'Nincs jogosultságod a házassági adatok szerkesztéséhez.');
                header('Location: ' . $redirectUrl);
                exit;
            }

            // Determine is_current status and manage other relationships
            if ($marriageEndDate === '') {
                // User intends to make this the current relationship.
                // First, ensure no other relationship is marked as current for this pastor.
                $resetCurrentStmt = $pdo->prepare("
                    UPDATE pastor_relationships
                    SET is_current = FALSE, updated_at = NOW()
                    WHERE pastor_uuid = :pastor_uuid
                    AND family_uuid != :family_uuid
                    AND is_current = TRUE
                ");
                $resetCurrentStmt->execute([
                    ':pastor_uuid' => $pastorUuid,
                    ':family_uuid' => $familyUuid
                ]);
                $isCurrent = true; // This one becomes the current one.
            } else {
                // If an end date is set, the relationship is not current.
                $isCurrent = false;
            }

            $sql = "
                UPDATE pastor_relationships SET
                    marriage_date = :marriage_date,
                    marriage_place = :marriage_place,
                    marriage_end_date = :marriage_end_date,
                    is_current = :is_current,
                    updated_at = NOW()
                WHERE family_uuid = :family_uuid AND pastor_uuid = :pastor_uuid
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':marriage_date',    $marriageDate !== '' ? $marriageDate : null);
            $stmt->bindValue(':marriage_place',   $marriagePlace !== '' ? $marriagePlace : null);
            $stmt->bindValue(':marriage_end_date', $marriageEndDate !== '' ? $marriageEndDate : null);
            $stmt->bindValue(':is_current',       $isCurrent, \PDO::PARAM_BOOL);
            $stmt->bindValue(':family_uuid',      $familyUuid);
            $stmt->bindValue(':pastor_uuid',      $pastorUuid);
            $stmt->execute();

            $pdo->commit();
            flash_set('success', 'Házassági adatok sikeresen frissítve.');
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[FamilyController::updateMarriage][ERR] ' . $e->getMessage());
            flash_set('error', 'Hiba történt a házassági adatok frissítése közben.');
        }

        header('Location: ' . $redirectUrl);
        exit;
    }


}
