<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\DTO\ProfileDTO;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('can create profile DTO with valid data', function () {
    $dto = new ProfileDTO(
        name: 'John Doe',
        email: 'john@example.com'
    );

    expect($dto->name)->toBe('John Doe');
    expect($dto->email)->toBe('john@example.com');
});

test('can create profile DTO from array', function () {
    $data = [
        'name'  => 'Jane Doe',
        'email' => 'jane@example.com',
    ];

    $dto = ProfileDTO::from($data);

    expect($dto->name)->toBe('Jane Doe');
    expect($dto->email)->toBe('jane@example.com');
});

test('can convert profile DTO to array', function () {
    $dto = new ProfileDTO(
        name: 'John Doe',
        email: 'john@example.com'
    );

    $array = $dto->toArray();

    expect($array)->toBe([
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

test('profile DTO has correct validation rules structure', function () {
    $user = User::factory()->create();

    // Create a mock DTO that can resolve the user
    $dto = new class('John Doe', 'john@example.com') extends ProfileDTO
    {
        private $mockUser;

        public function __construct(string $name, string $email, ?User $user = null)
        {
            parent::__construct($name, $email);
            $this->mockUser = $user;
        }

        protected function user(): ?User
        {
            return $this->mockUser;
        }
    };

    $dtoWithUser = new $dto('John Doe', 'john@example.com', $user);
    $rules       = $dtoWithUser->rules();

    expect($rules['name'])->toEqual(['required', 'string', 'max:255']);
    expect($rules['email'])->toContain('required', 'string', 'lowercase', 'email', 'max:255');
});

test('profile DTO validation rules include unique constraint', function () {
    $user = User::factory()->create();

    // Create a mock DTO that can resolve the user
    $dto = new class('John Doe', 'john@example.com') extends ProfileDTO
    {
        private $mockUser;

        public function __construct(string $name, string $email, ?User $user = null)
        {
            parent::__construct($name, $email);
            $this->mockUser = $user;
        }

        protected function user(): ?User
        {
            return $this->mockUser;
        }
    };

    $dtoWithUser = new $dto('John Doe', 'john@example.com', $user);
    $rules       = $dtoWithUser->rules();

    expect($rules)->toHaveKey('email');
    // Just verify the unique rule is in the array (comparing objects is complex)
    $hasUniqueRule = false;
    foreach ($rules['email'] as $rule) {
        if ($rule instanceof \Illuminate\Validation\Rules\Unique) {
            $hasUniqueRule = true;
            break;
        }
    }
    expect($hasUniqueRule)->toBeTrue();
});
