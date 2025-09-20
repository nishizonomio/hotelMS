<?php
use Housekeeping\Supplies\DB\InventoryLogger;
use Housekeeping\Supplies\Adapters\InventoryAdapter;

final class SupplyService {
    private SupplyRepository $repo;
    private $inventoryAdapter;
    private $logger;

    public function __construct(SupplyRepository $repo, $inventoryAdapter = null) {
        $this->repo = $repo;
        $this->inventoryAdapter = $inventoryAdapter;
        $this->logger = new InventoryLogger();
    }

    public function list(): array {
        try {
            $supplies = $this->repo->getAll();
            
            // Include items from main inventory if adapter is available
            if ($this->inventoryAdapter) {
                $mainInventoryItems = $this->inventoryAdapter->getRelevantInventoryItems();
                foreach ($mainInventoryItems as $item) {
                    $supplies[] = [
                        'item_name' => $item['item_name'],
                        'category' => $item['category'],
                        'quantity' => $item['quantity_in_stock'],
                        'reorder_level' => $item['reorder_level'],
                        'source' => 'main_inventory',
                        'item_id' => $item['item_id']
                    ];
                }
            }
            
            return $supplies;
        } catch (Exception $e) {
            $this->logger->logOperation('list_supplies', [], $e->getMessage());
            throw $e;
        }
    }

    public function counts(): array {
        try {
            $counts = $this->repo->getCounts();
            
            // Initialize counts if not set
            if (!isset($counts['total_items'])) $counts['total_items'] = 0;
            if (!isset($counts['low_stock'])) $counts['low_stock'] = 0;
            
            // Include main inventory counts if adapter is available
            if ($this->inventoryAdapter) {
                $mainInventoryItems = $this->inventoryAdapter->getRelevantInventoryItems();
                $counts['total_items'] += count($mainInventoryItems);
                
                $lowStockItems = $this->inventoryAdapter->getItemsNeedingReorder();
                $counts['low_stock'] += count($lowStockItems);
            }
            
            return $counts;
        } catch (Exception $e) {
            $this->logger->logOperation('get_counts', [], $e->getMessage());
            throw $e;
        }
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

