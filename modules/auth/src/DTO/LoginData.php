<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Spatie\LaravelData\Data;

class LoginData extends Data
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    public static function from(mixed ...$payloads): static
    {
        // Normalize email in the first payload if it's an array
        if (count($payloads) > 0 && is_array($payloads[0]) && isset($payloads[0]['email'])) {
            $payloads[0]['email'] = strtolower(trim($payloads[0]['email']));
        }

        return parent::from(...$payloads);
    }
}
