<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Illuminate\Validation\Rules\Password;
use Modules\Auth\Models\User;
use Spatie\LaravelData\Data;

class RegisterDTO extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $password_confirmation = null,
    ) {}

    public static function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
