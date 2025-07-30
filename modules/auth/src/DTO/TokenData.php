<?php

declare(strict_types=1);

namespace Modules\Auth\DTO;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TokenData extends Data
{
    public function __construct(
        public string $access_token,
        public string $token_type = 'Bearer',
        public ?int $expires_in = null,
    ) {}
}
