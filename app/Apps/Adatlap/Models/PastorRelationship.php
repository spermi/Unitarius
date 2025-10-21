<?php
namespace App\Apps\Adatlap\Models;

use Core\DB;
use PDO;

/**
 * PastorRelationship model
 * Handles marital and historical links between pastors
 */
class PastorRelationship
{
    private PDO $pdo;
    private string $table = 'pastor_relationships';
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

    /** Get all relationships for a specific pastor */
    public function byPastor(string $pastorUuid): array
    {
        $sql = "SELECT r.*, fm.name AS spouse_name, p2.full_name AS spouse_pastor_name
                FROM {$this->table} r
                LEFT JOIN family_members fm ON fm.uuid = r.spouse_uuid
                LEFT JOIN pastors p2 ON p2.uuid = r.spouse_pastor_uuid
                WHERE r.pastor_uuid = ?
                ORDER BY r.start_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$pastorUuid]);
        return $stmt->fetchAll();
    }

    /** Get the current active relationship of a pastor */
    public function current(string $pastorUuid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE pastor_uuid = ? AND is_current = true LIMIT 1"
        );
        $stmt->execute([$pastorUuid]);
        return $stmt->fetch() ?: null;
    }

    /** End current relationship safely */
    public function closeCurrent(string $pastorUuid): bool
    {
        $sql = "UPDATE {$this->table}
                SET is_current = false, end_date = now()
                WHERE pastor_uuid = ? AND is_current = true";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$pastorUuid]);
    }
}
