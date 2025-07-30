<?php

namespace Modules\Auth\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\Passkey;
use Modules\Auth\Models\User;

class PasskeyFactory extends Factory
{
    protected $model = Passkey::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'name'             => $this->faker->words(2, true),
            'credential_id'    => $this->faker->uuid(),
            'public_key'       => base64_encode($this->faker->randomLetter()),
            'sign_count'       => 0,
            'aaguid'           => $this->faker->uuid(),
            'transports'       => ['usb'],
            'type'             => 'public-key',
            'attestation_data' => null,
            'last_used_at'     => null,
        ];
    }
}
