<?php
final class SupplyService {
    private SupplyRepository $repo;

    public function __construct(SupplyRepository $repo) {
        $this->repo = $repo;
    }

    public function list(): array {
        return $this->repo->getAll();
    }

    public function counts(): array {
        return $this->repo->getCounts();
    }

    public function save(array $data): void {
        // Basic validation
        if ($data['quantity'] < 0) throw new InvalidArgumentException("Quantity cannot be negative.");
        if ($data['reorder_level'] < 0) throw new InvalidArgumentException("Reorder level cannot be negative.");

        if (isset($data['item_id'])) {
            $this->repo->update($data['item_id'], $data['quantity'], $data['reorder_level']);
        } else {
            $this->repo->upsert($data['item_name'], $data['category'], $data['quantity'], $data['unit'], $data['reorder_level']);
        }
    }

    public function delete(int $item_id): void {
        $this->repo->delete($item_id);
    }
}
?>

