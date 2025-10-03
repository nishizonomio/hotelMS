<?php
namespace CRM\Lib;

class GuestService {
    private GuestRepository $repo;

    public function __construct(GuestRepository $repo) {
        $this->repo = $repo;
    }

    public function listGuests(string $search = ''): array {
        return $this->repo->getAll($search);
    }

    public function createGuest(array $data): array {
        // basic validation
        if (empty($data['name']) || empty($data['email']) || empty($data['phone'])) {
            throw new \InvalidArgumentException('Missing required fields: name, email, phone');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
        // ensure unique email
        // (left to repository or DB unique constraint)
        return $this->repo->create($data);
    }

    public function updateGuest(array $data): bool {
        return $this->repo->update($data);
    }

    public function deleteGuest(int $id): bool {
        return $this->repo->delete($id);
    }
}
