<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Spatie\LaravelData\Data;

class ForgotPasswordData extends Data
{
    public function __construct(
        public string $email
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }
}
