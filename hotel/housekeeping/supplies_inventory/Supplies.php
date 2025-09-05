<?php
final class Supply {
    public int $item_id;
    public string $item_name;
    public string $category;
    public int $quantity;
    public string $unit;
    public int $reorder_level;

    public function __construct(array $row) {
        $this->item_id       = (int)$row['item_id'];
        $this->item_name     = $row['item_name'];
        $this->category      = $row['category'];
        $this->quantity      = (int)$row['quantity'];
        $this->unit          = $row['unit'];
        $this->reorder_level = (int)$row['reorder_level'];
    }
}
?>
