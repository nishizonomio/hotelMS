<?php
namespace CRM\Lib;

class GuestRepository {
    private \PDO $db;
    private array $columns = [];

    public function __construct(\PDO $db) {
        $this->db = $db;
        $this->columns = $this->detectColumns();
    }

    private function detectColumns(): array {
        try {
            $stmt = $this->db->query("DESCRIBE guests");
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getAll(string $search = ''): array {
        $hasFirstLast = in_array('first_name', $this->columns) && in_array('last_name', $this->columns);
        $hasFull = in_array('name', $this->columns);

        $sql = "SELECT * FROM guests";
        $params = [];
        if (!empty($search)) {
            if ($hasFirstLast && $hasFull) {
                $sql .= " WHERE (CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,'')) LIKE :search OR name LIKE :search OR email LIKE :search)";
            } elseif ($hasFirstLast) {
                $sql .= " WHERE (CONCAT(IFNULL(first_name,''), ' ', IFNULL(last_name,'')) LIKE :search OR email LIKE :search)";
            } else {
                $sql .= " WHERE (name LIKE :search OR email LIKE :search)";
            }
            $params[':search'] = "%$search%";
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $guests = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // normalize
        foreach ($guests as &$g) {
            if (isset($g['guest_id'])) $g['id'] = $g['guest_id'];
            if ($hasFirstLast && !$hasFull) $g['name'] = trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? ''));
            $phoneField = 'phone';
            if (in_array('first_phone', $this->columns)) $phoneField = 'first_phone';
            elseif (in_array('phone_number', $this->columns)) $phoneField = 'phone_number';
            if (isset($g[$phoneField])) $g['phone'] = $g[$phoneField];
            $g['location'] = $g['location'] ?? 'Unknown';
            $g['loyalty_tier'] = $g['loyalty_tier'] ?? 'bronze';
        }

        return $guests;
    }

    public function create(array $data): array {
        $hasFirstLast = in_array('first_name', $this->columns) && in_array('last_name', $this->columns);
        $hasFull = in_array('name', $this->columns);

        $fields = [];
        $placeholders = [];
        $params = [];

        if ($hasFirstLast) {
            $full = trim($data['name'] ?? '');
            $parts = explode(' ', $full, 2);
            $fields[] = 'first_name'; $placeholders[] = ':first_name'; $params[':first_name'] = $parts[0] ?? '';
            $fields[] = 'last_name'; $placeholders[] = ':last_name'; $params[':last_name'] = $parts[1] ?? '';
        }
        if ($hasFull) { $fields[] = 'name'; $placeholders[] = ':name'; $params[':name'] = $data['name'] ?? ''; }

        $fields = array_merge($fields, ['email','phone','loyalty_tier','location','notes','created_at']);
        $placeholders = array_merge($placeholders, [':email', ':phone', ':loyalty_tier', ':location', ':notes', 'NOW()']);

        $params[':email'] = $data['email'];
        $params[':phone'] = $data['phone'];
        $params[':loyalty_tier'] = $data['loyalty_tier'] ?? 'bronze';
        $params[':location'] = $data['location'] ?? 'Unknown';
        $params[':notes'] = $data['notes'] ?? '';

        $sql = "INSERT INTO guests (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $id = $this->db->lastInsertId();
        $stmt = $this->db->prepare("SELECT * FROM guests WHERE id = :id OR guest_id = :id");
        $stmt->execute([':id' => $id]);
        $new = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (isset($new['guest_id'])) $new['id'] = $new['guest_id'];
        if ($hasFirstLast && !$hasFull) $new['name'] = trim(($new['first_name'] ?? '') . ' ' . ($new['last_name'] ?? ''));
        return $new ?: [];
    }

    public function update(array $data): bool {
        $id = $data['id'] ?? null; if (!$id) return false;
        $update = [];
        $params = [':id' => $id];

        $phoneField = 'phone';
        if (in_array('first_phone', $this->columns)) $phoneField = 'first_phone';
        elseif (in_array('phone_number', $this->columns)) $phoneField = 'phone_number';

        if (isset($data['name'])) {
            if (in_array('first_name', $this->columns) && in_array('last_name', $this->columns)) {
                $parts = explode(' ', trim($data['name']), 2);
                $update[] = 'first_name = :first_name'; $update[] = 'last_name = :last_name';
                $params[':first_name'] = $parts[0] ?? ''; $params[':last_name'] = $parts[1] ?? '';
            }
            if (in_array('name', $this->columns)) {
                $update[] = 'name = :name'; $params[':name'] = $data['name'];
            }
        }
        if (isset($data['email'])) { $update[] = 'email = :email'; $params[':email'] = $data['email']; }
        if (isset($data['phone'])) { $update[] = "$phoneField = :phone"; $params[':phone'] = $data['phone']; }
        if (isset($data['loyalty_tier'])) { $update[] = 'loyalty_tier = :loyalty_tier'; $params[':loyalty_tier'] = $data['loyalty_tier']; }
        if (isset($data['location'])) { $update[] = 'location = :location'; $params[':location'] = $data['location']; }
        if (isset($data['notes'])) { $update[] = 'notes = :notes'; $params[':notes'] = $data['notes']; }

        if (empty($update)) return false;
        $sql = "UPDATE guests SET " . implode(', ', $update) . " WHERE id = :id OR guest_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return true;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM guests WHERE id = :id OR guest_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
