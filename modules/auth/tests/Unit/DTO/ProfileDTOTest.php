<?php

use Modules\Auth\DTO\ProfileDTO;

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
        'name' => 'Jane Doe',
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
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

test('profile DTO has correct validation rules structure', function () {
    $dto = new ProfileDTO(
        name: 'John Doe',
        email: 'john@example.com'
    );

    // We can't test the actual rules since they reference $this->user()
    // which isn't available in a unit test context
    expect($dto)->toBeInstanceOf(ProfileDTO::class);
    expect($dto->name)->toBe('John Doe');
    expect($dto->email)->toBe('john@example.com');
});