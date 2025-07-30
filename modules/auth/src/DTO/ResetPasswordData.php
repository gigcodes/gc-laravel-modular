<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Spatie\LaravelData\Data;

class ResetPasswordData extends Data
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
        public string $password_confirmation
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', 'min:8'],
            'password_confirmation' => ['required'],
        ];
    }
}
