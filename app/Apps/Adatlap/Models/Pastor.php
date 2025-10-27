<?php
namespace App\Apps\Adatlap\Models;

use Core\DB;
use PDO;

/**
 * Pastor model
 * Handles pastors table (basic info and linked families)
 */
class Pastor
{
    private PDO $pdo;
    private string $table = 'pastors';
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

    /** Find pastor by user UUID */
    public function findByUser(string $userUuid): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_uuid = ?");
        $stmt->execute([$userUuid]);
        return $stmt->fetch() ?: null;
    }

    /** Get all pastors with linked family names */
    public function allWithFamilies(): array
    {
        $sql = "SELECT p.*,
                        f.family_name
                FROM {$this->table} p
                LEFT JOIN pastor_relationships pr
                        ON pr.pastor_uuid = p.uuid AND pr.is_current = TRUE
                LEFT JOIN families f
                        ON f.uuid = pr.family_uuid
                ORDER BY p.full_name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
