<?php
declare(strict_types=1);
class MonitorModel
{
    private PDO $db;
    public function __construct() { $this->db = db(); }
    public function workerSeen(): int { return (int) $this->db->query("SELECT seen_at FROM worker_state WHERE id=1")->fetchColumn(); }
    public function lastId(): int { return (int) $this->db->lastInsertId(); }
    public function all(bool $public): array { return $this->db->query('SELECT m.*, c.up, c.checked_at FROM monitors m LEFT JOIN checks c ON c.id =
    (SELECT id FROM checks WHERE monitor_id=m.id ORDER BY id DESC LIMIT 1)'
    . ($public ? ' WHERE m.listed=1' : '') . ' ORDER BY m.name COLLATE NOCASE, m.id')->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC); }
    public function update(array $values): void
    {
        $this->db->prepare('UPDATE monitors SET name=?, url=?, interval_seconds=?, kind=?, keyword=?, listed=?,
                    next_check=0, revision=revision+1 WHERE id=?')->execute($values);
    }
    public function create(array $values): void
    {
        $this->db->prepare('INSERT INTO monitors(name,url,interval_seconds,kind,keyword,listed)
                    VALUES (?,?,?,?,?,?)')->execute($values);
    }
    public function toggle(array $values): void
    {
        $this->db->prepare('UPDATE monitors SET paused=1-paused, next_check=0, revision=revision+1 WHERE id=?')->execute($values);
    }
    public function queueCheck(array $values): void
    {
        $this->db->prepare('UPDATE monitors SET next_check=0, revision=revision+1 WHERE id=? AND paused=0')->execute($values);
    }
    public function delete(array $values): void
    {
        $this->db->prepare('DELETE FROM monitors WHERE id=?')->execute($values);
    }
    public function history(int $id, int $limit): array
    {
        $query = $this->db->prepare("SELECT * FROM checks WHERE monitor_id=? ORDER BY id DESC LIMIT ?");
        $query->execute([$id, $limit]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
