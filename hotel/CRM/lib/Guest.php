<?php
namespace CRM\Lib;

class Guest {
    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $loyalty_tier = 'bronze';
    public string $location = 'Unknown';
    public string $notes = '';
    public ?string $created_at = null;

    public function __construct(array $data = []) {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) $this->{$k} = $v;
        }
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'loyalty_tier' => $this->loyalty_tier,
            'location' => $this->location,
            'notes' => $this->notes,
            'created_at' => $this->created_at
        ];
    }
}
