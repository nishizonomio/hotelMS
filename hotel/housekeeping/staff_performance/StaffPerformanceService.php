<?php
/**
 * StaffPerformanceService
 * Thin service layer that validates and maps request data to the
 * StaffPerformanceRepository. Provides methods used by hp_performance.php:
 *  - add(array)
 *  - update(array)
 *  - delete(int)
 *  - list(): array
 *  - stats(): array
 *  - staffList(): array
 *  - tasksList(): array
 */
class StaffPerformanceService {
    private $repo;

    public function __construct($repo) {
        $this->repo = $repo;
    }

    /**
     * Add a performance record. Accepts $_POST-like array.
     */
    public function add(array $data): void {
        $payload = $this->mapRequestToPayload($data, false);
        $this->repo->insert($payload);
    }

    /**
     * Update an existing performance record. Expects performance_id in $data.
     */
    public function update(array $data): void {
        $payload = $this->mapRequestToPayload($data, true);
        $this->repo->update($payload);
    }

    /** Delete by id */
    public function delete(int $performanceId): void {
        $this->repo->delete($performanceId);
    }

    /** Return list of performance records */
    public function list(): array {
        return $this->repo->getAll();
    }

    /** Return staff dropdown list */
    public function staffList(): array {
        return $this->repo->getStaffList();
    }

    /** Return tasks dropdown list */
    public function tasksList(): array {
        return $this->repo->getTasksList();
    }

    /**
     * Compute simple aggregated stats expected by the UI
     * Returns: ['total'=>int, 'avgTasks'=>float, 'avgRating'=>float]
     */
    public function stats(): array {
        $rows = $this->repo->getAll();
        $total = count($rows);
        $sumTasks = 0;
        $sumRating = 0;
        foreach ($rows as $r) {
            $sumTasks += (int)($r['tasks_completed'] ?? 0);
            $sumRating += (float)($r['quality_rating'] ?? 0);
        }
        $avgTasks = $total ? $sumTasks / $total : 0;
        $avgRating = $total ? $sumRating / $total : 0;
        return ['total' => $total, 'avgTasks' => $avgTasks, 'avgRating' => $avgRating];
    }

    /**
     * Map incoming request to repository payload.
     * If $isUpdate true expects 'performance_id' key in $data
     */
    private function mapRequestToPayload(array $data, bool $isUpdate = false): array {
        $payload = [];
        if ($isUpdate) {
            if (empty($data['performance_id'])) throw new InvalidArgumentException('performance_id is required for update');
            $payload['performance_id'] = (int)$data['performance_id'];
        }

        // Required numeric fields
        $payload['staff_id'] = isset($data['staff_id']) ? (int)$data['staff_id'] : 0;
        $payload['task_id'] = !empty($data['task_id']) ? (int)$data['task_id'] : null;
        $payload['date'] = $data['date'] ?? date('Y-m-d');
        $payload['tasks_completed'] = isset($data['tasks_completed']) ? (int)$data['tasks_completed'] : 0;

        // average_completion_time may be a time string (HH:MM) or minutes; convert to minutes
        $avgTime = $data['average_completion_time'] ?? ($data['avg_time_minutes'] ?? null);
        $payload['avg_time_minutes'] = $this->normalizeTimeToMinutes($avgTime);

        $payload['quality_rating'] = isset($data['quality_rating']) ? (int)$data['quality_rating'] : 0;
        $payload['feedback'] = $data['feedback'] ?? '';
        $payload['evaluator_id'] = isset($data['evaluator_id']) ? (int)$data['evaluator_id'] : 0;

        return $payload;
    }

    /**
     * Normalize a time input to integer minutes.
     * Accepts null, integer minutes, or time string "HH:MM" or "H:MM:SS".
     */
    private function normalizeTimeToMinutes($value): int {
        if ($value === null || $value === '') return 0;
        if (is_numeric($value)) return (int)$value;
        // Expect formats like HH:MM or HH:MM:SS
        if (is_string($value) && preg_match('/^(\d{1,2}):(\d{2})(:(\d{2}))?$/', $value, $m)) {
            $hours = (int)$m[1];
            $minutes = (int)$m[2];
            return $hours * 60 + $minutes;
        }
        // fallback
        return 0;
    }
}

?>