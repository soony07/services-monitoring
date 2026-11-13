<?php
declare(strict_types=1);
class SettingsModel
{
    private PDO $db;
    public function __construct()
    {
        $this->db = db();
        $this->db->exec("CREATE TABLE IF NOT EXISTS settings (id INTEGER PRIMARY KEY CHECK(id=1), name TEXT NOT NULL, language TEXT NOT NULL, accent TEXT NOT NULL)");
    }
    public function get(): ?array
    {
        return $this->db->query('SELECT * FROM settings WHERE id=1')->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function save(array $input, bool $setup): bool
    {
        $name = trim((string) ($input['name'] ?? ''));
        $language = $input['language'] ?? '';
        $accent = $input['accent'] ?? '';
        if ($name === '' || strlen($name) > 100 || !in_array($language, ['de', 'en'], true)
            || !is_string($accent) || !preg_match('/^#[0-9a-f]{6}$/iD', $accent)) {
            throw new InvalidArgumentException('Bitte alle Einstellungen überprüfen.');
        }
        $sql = $setup
            ? 'INSERT OR IGNORE INTO settings (id,name,language,accent) VALUES (1,?,?,?)'
            : 'UPDATE settings SET name=?,language=?,accent=? WHERE id=1';
        $query = $this->db->prepare($sql);
        $query->execute([$name, $language, strtolower($accent)]);
        return $query->rowCount() > 0;
    }
}
