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

    try {
        if (can('adatlap.family.manage')) {
            // keep admin branch as-is for now
            $families = $this->families->allFamilies(); // unchanged
        } else {
            $userUuid = $user['uuid'] ?? '';
            if ($userUuid !== '') {
                // find pastor for current user
                $pastor = $this->pastors->findByUser($userUuid); // returns ['uuid'=>...]
                $pastorUuid = $pastor['uuid'] ?? null;

                $pdo = DB::pdo();
                if ($pastorUuid) {
                    // Order: current first, then newest created
                    $stmt = $pdo->prepare('
                        SELECT f.*, COALESCE(pr.is_current, false) AS is_current
                        FROM families f
                        LEFT JOIN pastor_relationships pr
                        ON pr.family_uuid = f.uuid
                        AND pr.pastor_uuid = :pastor_uuid
                        WHERE f.created_by_uuid = :user_uuid
                        ORDER BY pr.is_current DESC, f.created_at DESC
                    ');
                    $stmt->execute([
                        ':pastor_uuid' => $pastorUuid,
                        ':user_uuid'   => $userUuid,
                    ]);
                } else {
                    // Fallback: original behavior
                    $stmt = $pdo->prepare('
                        SELECT f.*, false AS is_current
                        FROM families f
                        WHERE f.created_by_uuid = :user_uuid
                        ORDER BY f.created_at DESC
                    ');
                    $stmt->execute([':user_uuid' => $userUuid]);
                }

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
        require_can('adatlap.lelkesz');

        if (!verify_csrf()) {
            flash_set('error', 'Érvénytelen vagy hiányzó biztonsági token.');
            header('Location: ' . base_url('/adatlap/family'));
            exit;
        }

        $currentUser = \current_user();
        $userUuid    = $currentUser['uuid'] ?? null;

        $familyUuid        = trim($_POST['family_uuid'] ?? '');
        $relationCodeInput = trim($_POST['relation_code'] ?? '');
        $name              = trim($_POST['name'] ?? '');
        $birthDate         = trim($_POST['birth_date'] ?? '');
        $deathDate         = trim($_POST['death_date'] ?? '');
        // optional: if spouse is also a pastor, frontend küldheti (UUID selectből)
        $spousePastorUuid  = trim($_POST['spouse_pastor_uuid'] ?? '');

        // gender from form (radio): 'male' | 'female' | ''
        $gender = strtolower(trim($_POST['gender'] ?? ''));
        if ($gender !== 'male' && $gender !== 'female') {
            $gender = null;
        }

        $redirectUrl = base_url('/adatlap/family/' . $familyUuid);

        if ($familyUuid === '' || $relationCodeInput === '') {
            flash_set('error', 'Hiányzó kötelező mezők (család vagy kapcsolat típus).');
            header('Location: ' . $redirectUrl);
            exit;
        }

        try {
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
            $stmt->execute([':user_uuid' => $userUuid, ':family_uuid' => $familyUuid]);
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
            $stmt->execute([':v' => $relationCodeInput]);
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
            $stmt = $pdo->prepare("SELECT uuid, full_name FROM pastors WHERE user_uuid = :u LIMIT 1");
            $stmt->execute([':u' => $userUuid]);
            $pastorRow = $stmt->fetch(\PDO::FETCH_ASSOC);
            $currentPastorUuid = $pastorRow['uuid'] ?? null;
            $currentPastorName = $pastorRow['full_name'] ?? null;

            // * Helper: ensure pastor has a member row (husband|wife) in this family
            $ensurePastorMember = function() use ($pdo, $familyUuid, $userUuid, $currentPastorUuid, $currentPastorName): string {
                $q = $pdo->prepare("
                    SELECT uuid FROM family_members
                    WHERE family_uuid = :f
                    AND (pastor_uuid = :p OR user_uuid = :u)
                    AND relation_code IN ('husband','wife')
                    LIMIT 1
                ");
                $q->execute([':f'=>$familyUuid, ':p'=>$currentPastorUuid, ':u'=>$userUuid]);
                $mid = $q->fetchColumn();
                if ($mid) {
                    if ($currentPastorUuid) {
                        $u = $pdo->prepare("UPDATE family_members SET pastor_uuid = :p WHERE uuid = :m AND pastor_uuid IS NULL");
                        $u->execute([':p'=>$currentPastorUuid, ':m'=>$mid]);
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
                $q->execute([':f'=>$familyUuid]);
                $c = $q->fetch(\PDO::FETCH_ASSOC) ?: ['husbands'=>0,'wives'=>0];
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
                    ':f'=>$familyUuid,
                    ':u'=>$userUuid,
                    ':p'=>$currentPastorUuid,
                    ':n'=>$currentPastorName,
                    ':r'=>$role,
                    ':rg'=>$roleGender,
                ]);
                return (string)$q->fetchColumn();
            };

            // * Helper: read current husband/wife member uuids in this family
            $fetchSpouses = function() use ($pdo, $familyUuid): array {
                $h = $pdo->prepare("SELECT uuid FROM family_members WHERE family_uuid = :f AND relation_code = 'husband' LIMIT 1");
                $w = $pdo->prepare("SELECT uuid FROM family_members WHERE family_uuid = :f AND relation_code = 'wife'    LIMIT 1");
                $h->execute([':f'=>$familyUuid]);
                $w->execute([':f'=>$familyUuid]);
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
            // parent_uuid maybe should be removed?????
            //---------------------------------------------------------
            $insertSql = "
                INSERT INTO family_members 
                (uuid, family_uuid, user_uuid, pastor_uuid, parent_uuid, father_uuid, mother_uuid, name, relation_code, gender, birth_date, death_date, created_at, updated_at)
                VALUES 
                (gen_random_uuid(), :family_uuid, NULL, :pastor_uuid, :parent_uuid, :father_uuid, :mother_uuid, :name, :relation_code, :gender, :birth_date, :death_date, NOW(), NOW())
                RETURNING uuid
            ";
            $stmt = $pdo->prepare($insertSql);
            $stmt->bindValue(':family_uuid', $familyUuid);
            $stmt->bindValue(':pastor_uuid', ($isSpouse && $spousePastorUuid !== '') ? $spousePastorUuid : null);
            $stmt->bindValue(':parent_uuid', $parentUuid);
            $stmt->bindValue(':father_uuid', $fatherUuid);
            $stmt->bindValue(':mother_uuid', $motherUuid);
            $stmt->bindValue(':name', $name !== '' ? $name : null);
            $stmt->bindValue(':relation_code', $relationCode);
            $stmt->bindValue(':gender', $gender);
            $stmt->bindValue(':birth_date', $birthDate !== '' ? $birthDate : null);
            $stmt->bindValue(':death_date', $deathDate !== '' ? $deathDate : null);
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
                    // Ha a lelkésznek már van máshol current PR, az új bejegyzés ne legyen current
                    $cur = $pdo->prepare("
                        SELECT family_uuid FROM pastor_relationships
                        WHERE pastor_uuid = :p AND is_current = TRUE
                        LIMIT 1
                    ");
                    $cur->execute([':p' => $pastorUuid]);
                    $currentFamilyForPastor = $cur->fetchColumn() ?: null;

                    // bool -> 'true'/'false' + cast to boolean az SQL-ben (Postgres)
                    $makeCurrent = ($currentFamilyForPastor === null || $currentFamilyForPastor === $familyUuid);
                    $icVal = $makeCurrent ? 'true' : 'false';

                    if ($spousePastorUuid !== '') {
                        // spouse is pastor
                        $u = $pdo->prepare("
                            UPDATE pastor_relationships
                            SET spouse_pastor_uuid = :sp,
                                spouse_uuid        = NULL,
                                is_current         = :ic::boolean
                            WHERE pastor_uuid = :p AND family_uuid = :f
                        ");
                        $u->execute([':sp'=>$spousePastorUuid, ':ic'=>$icVal, ':p'=>$pastorUuid, ':f'=>$familyUuid]);

                        if ($u->rowCount() === 0) {
                            $i = $pdo->prepare("
                                INSERT INTO pastor_relationships
                                (uuid, pastor_uuid, family_uuid, spouse_pastor_uuid, is_current)
                                VALUES
                                (gen_random_uuid(), :p, :f, :sp, :ic::boolean)
                            ");
                            $i->execute([':p'=>$pastorUuid, ':f'=>$familyUuid, ':sp'=>$spousePastorUuid, ':ic'=>$icVal]);
                        }
                    } else {
                        // spouse is NOT pastor
                        $u = $pdo->prepare("
                            UPDATE pastor_relationships
                            SET spouse_uuid        = :sm,
                                spouse_pastor_uuid = NULL,
                                is_current         = :ic::boolean
                            WHERE pastor_uuid = :p AND family_uuid = :f
                        ");
                        $u->execute([':sm'=>$newMemberUuid, ':ic'=>$icVal, ':p'=>$pastorUuid, ':f'=>$familyUuid]);

                        if ($u->rowCount() === 0) {
                            $i = $pdo->prepare("
                                INSERT INTO pastor_relationships
                                (uuid, pastor_uuid, family_uuid, spouse_uuid, is_current)
                                VALUES
                                (gen_random_uuid(), :p, :f, :sm, :ic::boolean)
                            ");
                            $i->execute([':p'=>$pastorUuid, ':f'=>$familyUuid, ':sm'=>$newMemberUuid, ':ic'=>$icVal]);
                        }
                    }
                }
            }

            //---------------------------------------------------------
            // 6) FAMILY-WIDE NORMALIZATION :
            //    After any insert (child or spouse), fill ALL children's missing father/mother
            //    from the current family's husband/wife members, if present.
            //---------------------------------------------------------
            $spouses = $fetchSpouses(); // reflect the just-inserted member too
            if (!empty($spouses['husband'])) {
                $u = $pdo->prepare("
                    UPDATE family_members 
                    SET father_uuid = COALESCE(father_uuid, :father)
                    WHERE family_uuid = :f
                    AND LOWER(relation_code) IN ('child','children')
                    AND father_uuid IS NULL
                ");
                $u->execute([':father' => $spouses['husband'], ':f' => $familyUuid]);
            }
            if (!empty($spouses['wife'])) {
                $u = $pdo->prepare("
                    UPDATE family_members
                    SET mother_uuid = COALESCE(mother_uuid, :mother)
                    WHERE family_uuid = :f
                    AND LOWER(relation_code) IN ('child','children')
                    AND mother_uuid IS NULL
                ");
                $u->execute([':mother' => $spouses['wife'], ':f' => $familyUuid]);
            }

            $pdo->commit();
            flash_set('success', 'Családtag sikeresen hozzáadva és a gyermekek szülői mezői frissítve.');
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            flash_set('error', 'Hiba történt a családtag mentése közben: ' . $e->getMessage());
        }

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
