<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Illuminate\Support\Carbon as LaravelCarbon;
use Modules\Auth\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AuthResponseData extends Data
{
    /**
     * @param array<string> $roles
     * @param array<string> $permissions
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $email_verified_at,
        public array $roles,
        public array $permissions,
        public ?string $last_login_at,
        public LaravelCarbon $created_at,
        public LaravelCarbon $updated_at,
    ) {}

    /**
     * Create from User model.
     *
     * @param User $user The user model
     */
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at?->toISOString(),
            roles: $user->roles?->pluck('name')->toArray() ?? [],
            permissions: method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name')->toArray() : [],
            last_login_at: $user->last_login_at?->toISOString(),
            created_at: $user->created_at,
            updated_at: $user->updated_at,
        );
    }
}
