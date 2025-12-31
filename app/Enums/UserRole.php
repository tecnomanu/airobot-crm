<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::SUPERVISOR => 'Supervisor',
            self::USER => 'Usuario',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'purple',
            self::SUPERVISOR => 'blue',
            self::USER => 'gray',
        };
    }

    /**
     * Check if role can manage users.
     */
    public function canManageUsers(): bool
    {
        return match ($this) {
            self::ADMIN, self::SUPERVISOR => true,
            self::USER => false,
        };
    }

    /**
     * Check if role can manage all clients.
     */
    public function canManageAllClients(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role can view all users globally.
     */
    public function canViewAllUsers(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role has full system access.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role is supervisor level.
     */
    public function isSupervisor(): bool
    {
        return $this === self::SUPERVISOR;
    }

    /**
     * Get all roles as options for select inputs.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            self::cases()
        );
    }
}

