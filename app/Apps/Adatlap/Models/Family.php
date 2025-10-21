<?php
namespace App\Apps\Adatlap\Models;

use Core\DB;
use PDO;

/**
 * Family model
 * Handles families table and linked members
 */
class Family
{
    private PDO $pdo;
    private string $table = 'families';
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

    /** Get all families */
    public function allFamilies(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    /** Find a family by UUID */
    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE uuid = ?");
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    /** Get all members linked to a family */
    public function members(string $familyUuid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM family_members WHERE family_uuid = ? ORDER BY is_primary DESC, birth_date ASC"
        );
        $stmt->execute([$familyUuid]);
        return $stmt->fetchAll();
    }
}
