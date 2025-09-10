<?php
final class MaintenanceService
{
    private MaintenanceRepository $repo;
    // Accept UI statuses and DB 'Resolved' interchangeably
    private array $allowedStatuses = ['Pending', 'In Progress', 'Completed', 'Resolved'];

    public function __construct(MaintenanceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list(): array
    {
        return $this->repo->getAll();
    }

    public function addTask(array $data): void
    {
        $data['status'] = $data['status'] ?? 'Pending';
        $data['remarks'] = $data['remarks'] ?? '';
        $this->validate($data);

        $this->repo->addTask(
            (int)$data['room_id'],
            $data['issue'],
            $data['reported_date'],
            $data['remarks'] ?? ''
        );
    }

    public function update(array $data): void
    {
        // If this is a status-only change (the inline select triggers a submit),
        // perform a lightweight status update instead of requiring full fields.
        if (isset($data['status']) && !isset($data['edit_task'])) {
            $status = $data['status'];
            // Map UI 'Completed' to DB 'Resolved'
            $dbStatus = $status === 'Completed' ? 'Resolved' : $status;
            $completed_date = $dbStatus === 'Resolved' ? date('Y-m-d') : null;
            $this->repo->updateStatus((int)$data['maintenance_id'], $dbStatus, $completed_date);
            return;
        }

        $this->validate($data);

        $this->repo->updateTask(
            (int)$data['maintenance_id'],
            (int)$data['room_id'],
            $data['issue'],
            $data['remarks'] ?? '',
            $data['completed_date'] ?? null
        );
    }

    public function updateStatus(int $id, string $status): void
    {
    // Map UI 'Completed' to DB 'Resolved'
    $dbStatus = $status === 'Completed' ? 'Resolved' : $status;
    $completed_date = $dbStatus === 'Resolved' ? date('Y-m-d') : null;
    $this->repo->updateStatus($id, $dbStatus, $completed_date);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteTask($id);
    }

    public function counts(): array
    {
        return $this->repo->getCounts();
    }

    public function completedCounts() {
        $list = $this->repo->getAll();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $last3Days = date('Y-m-d', strtotime('-3 days'));
        $lastMonth = date('Y-m', strtotime('-1 month'));

        $c = ['yesterday'=>0,'last3Days'=>0,'lastMonth'=>0];
        foreach($list as $t) {
            // repository may return DB status 'Resolved'
            if(($t['status']=='Completed' || $t['status']=='Resolved') && $t['completed_date']){
                if($t['completed_date']==$yesterday) $c['yesterday']++;
                if($t['completed_date'] >= $last3Days) $c['last3Days']++;
                if(substr($t['completed_date'],0,7)==$lastMonth) $c['lastMonth']++;
            }
        }
        return $c;
    }

    public function selectedMonthCount($month) {
        $list = $this->repo->getAll();
        $year = date('Y');
        $count = 0;
        foreach($list as $t) {
            if(($t['status']=='Completed' || $t['status']=='Resolved') && $t['completed_date']){
                $dt = strtotime($t['completed_date']);
                if(date('m',$dt)==$month && date('Y',$dt)==$year) $count++;
            }
        }
        return $count;
    }

    public function getRooms(): array
    {
        return $this->repo->getRooms();
    }

    private function validate(array $data): void
    {
        if (!in_array($data['status'] ?? 'Pending', $this->allowedStatuses, true)) {
            throw new InvalidArgumentException("Invalid status");
        }
        if (empty($data['room_id']) || empty($data['issue'])) {
            throw new InvalidArgumentException("Room and issue are required");
        }
    }
}
?>
