<?php

namespace App\Services\User;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    // =========================================================================
    // CRUD OPERATIONS
    // =========================================================================

    /**
     * Get paginated users with filters.
     */
    public function getUsers(array $filters = [], int $perPage = 15, ?User $viewer = null): LengthAwarePaginator
    {
        return $this->userRepository->paginate($filters, $perPage, $viewer);
    }

    /**
     * Get user by ID.
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id, ['client']);
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        $this->validateEmailUnique($data['email']);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $data['role'] = $data['role'] ?? UserRole::USER->value;
        $data['is_seller'] = $data['is_seller'] ?? false;
        $data['status'] = $data['status'] ?? UserStatus::ACTIVE->value;

        return $this->userRepository->create($data);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(int $id, array $data): User
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado');
        }

        if (!empty($data['email']) && $data['email'] !== $user->email) {
            $this->validateEmailUnique($data['email']);
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $this->userRepository->update($user, $data);
    }

    /**
     * Delete a user.
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado');
        }

        if ($user->isAdmin()) {
            $adminCount = $this->userRepository->countByRole(UserRole::ADMIN);
            if ($adminCount <= 1) {
                throw new \InvalidArgumentException('No se puede eliminar el único administrador');
            }
        }

        return $this->userRepository->delete($user);
    }

    // =========================================================================
    // SELLER OPERATIONS
    // =========================================================================

    /**
     * Get sellers for a specific client.
     */
    public function getSellersForClient(string $clientId): Collection
    {
        return $this->userRepository->getSellersForClient($clientId);
    }

    /**
     * Get sellers available for a campaign assignment.
     */
    public function getSellersForCampaign(?string $clientId): Collection
    {
        return $this->userRepository->getSellersForCampaign($clientId);
    }

    /**
     * Get all sellers.
     */
    public function getAllSellers(): Collection
    {
        return $this->userRepository->getAllSellers();
    }

    /**
     * Toggle seller status for a user.
     */
    public function toggleSellerStatus(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado');
        }

        return $this->userRepository->update($user, [
            'is_seller' => !$user->is_seller,
        ]);
    }

    /**
     * Toggle user active/inactive status.
     */
    public function toggleStatus(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado');
        }

        $newStatus = $user->status === UserStatus::ACTIVE
            ? UserStatus::INACTIVE->value
            : UserStatus::ACTIVE->value;

        return $this->userRepository->update($user, [
            'status' => $newStatus,
        ]);
    }

    // =========================================================================
    // AUTHORIZATION HELPERS
    // =========================================================================

    /**
     * Check if user can access users menu.
     */
    public function canAccessUsersMenu(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(User $user): bool
    {
        return $user->role->canManageUsers();
    }

    /**
     * Check if user can view all users globally.
     */
    public function canViewAllUsers(User $user): bool
    {
        return $user->role->canViewAllUsers();
    }

    /**
     * Check if user can manage a specific target user.
     */
    public function canManageUser(User $actor, User $targetUser): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        if ($actor->isSupervisor()) {
            if ($actor->isGlobalUser()) {
                return !$targetUser->isAdmin();
            }

            return $actor->client_id === $targetUser->client_id && !$targetUser->isAdmin();
        }

        return false;
    }

    /**
     * Check if user can create users with a specific role.
     */
    public function canCreateWithRole(User $actor, string $role): bool
    {
        if (!$actor->isAdmin() && $role === UserRole::ADMIN->value) {
            return false;
        }

        return true;
    }

    // =========================================================================
    // ROLE OPERATIONS
    // =========================================================================

    /**
     * Change user role.
     */
    public function changeRole(int $id, UserRole|string $role): User
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado');
        }

        $roleValue = $role instanceof UserRole ? $role->value : $role;

        return $this->userRepository->update($user, [
            'role' => $roleValue,
        ]);
    }

    /**
     * Assign user to a client.
     */
    public function assignToClient(int $userId, ?string $clientId): User
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new \InvalidArgumentException('Usuario no encontrado');
        }

        return $this->userRepository->update($user, [
            'client_id' => $clientId,
        ]);
    }

    // =========================================================================
    // VALIDATIONS
    // =========================================================================

    /**
     * Validate email is unique.
     */
    protected function validateEmailUnique(string $email, ?int $excludeId = null): void
    {
        $existing = $this->userRepository->findByEmail($email);

        if ($existing && $existing->id !== $excludeId) {
            throw new \InvalidArgumentException('Ya existe un usuario con ese email');
        }
    }
}
