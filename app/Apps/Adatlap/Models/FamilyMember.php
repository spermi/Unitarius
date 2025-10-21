<?php
namespace App\Apps\Adatlap\Models;

use Core\DB;
use PDO;

/**
 * FamilyMember model
 * Handles family_members table and recursive tree building
 */
class FamilyMember
{
    private PDO $pdo;
    private string $table = 'family_members';
    private string $lastWhere = '';
    private array $lastParams = [];

    public function __construct()
    {
        $this->pdo = DB::pdo();
    }

    /** Generic WHERE filter */
    public function where(string $column, $value): self
    {
        $this->lastWhere = "WHERE {$column} = ?";
        $this->lastParams = [$value];
        return $this;
    }

    /** Get first result from last WHERE */
    public function first(): ?array
    {
        $sql = "SELECT * FROM {$this->table} {$this->lastWhere} LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->lastParams);
        return $stmt->fetch() ?: null;
    }

    /** Get all members of a family */
    public function byFamily(string $familyUuid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE family_uuid = ? ORDER BY birth_date ASC"
        );
        $stmt->execute([$familyUuid]);
        return $stmt->fetchAll();
    }

    /** Get direct children of a member */
    public function childrenOf(string $memberUuid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE parent_uuid = ? ORDER BY birth_date ASC"
        );
        $stmt->execute([$memberUuid]);
        return $stmt->fetchAll();
    }

    /** Get parent of a given member */
    public function parentOf(string $memberUuid): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE uuid = ?");
        $stmt->execute([$memberUuid]);
        $member = $stmt->fetch();
        if (!$member || empty($member['parent_uuid'])) {
            return null;
        }

        $stmt2 = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE uuid = ?");
        $stmt2->execute([$member['parent_uuid']]);
        return $stmt2->fetch() ?: null;
    }

    /** Recursive family tree builder */
    public function buildTree(string $rootUuid): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE uuid = ?");
        $stmt->execute([$rootUuid]);
        $root = $stmt->fetch();
        if (!$root) return [];

        $node = [
            'uuid'       => $root['uuid'],
            'name'       => $root['name'],
            'relation'   => $root['relation'],
            'birth_date' => $root['birth_date'],
            'is_primary' => (bool)$root['is_primary']
        ];

        $children = $this->childrenOf($rootUuid);
        $node['children'] = [];
        foreach ($children as $child) {
            $node['children'][] = $this->buildTree($child['uuid']);
        }

        return $node;
    }
}
