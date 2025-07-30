<?php

use Modules\Auth\DTO\LoginData;

test('can create login data with required fields', function () {
    $data = new LoginData(
        email: 'user@example.com',
        password: 'password123'
    );

    expect($data->email)->toBe('user@example.com');
    expect($data->password)->toBe('password123');
    expect($data->remember)->toBeFalse();
});

test('can create login data with remember option', function () {
    $data = new LoginData(
        email: 'user@example.com',
        password: 'password123',
        remember: true
    );

    expect($data->email)->toBe('user@example.com');
    expect($data->password)->toBe('password123');
    expect($data->remember)->toBeTrue();
});

test('can create login data from array', function () {
    $array = [
        'email'    => 'test@example.com',
        'password' => 'secret',
        'remember' => true,
    ];

    $data = LoginData::from($array);

    expect($data->email)->toBe('test@example.com');
    expect($data->password)->toBe('secret');
    expect($data->remember)->toBeTrue();
});

test('normalizes email when creating from array', function () {
    $array = [
        'email'    => '  USER@EXAMPLE.COM  ',
        'password' => 'password',
    ];

    $data = LoginData::from($array);

    expect($data->email)->toBe('user@example.com');
    expect($data->password)->toBe('password');
});

test('can convert login data to array', function () {
    $data = new LoginData(
        email: 'user@example.com',
        password: 'password123',
        remember: true
    );

    $array = $data->toArray();

    expect($array)->toBe([
        'email'    => 'user@example.com',
        'password' => 'password123',
        'remember' => true,
    ]);
});

test('login data has correct validation rules', function () {
    $rules = LoginData::rules();

    expect($rules)->toHaveKey('email');
    expect($rules)->toHaveKey('password');
    expect($rules)->toHaveKey('remember');

    expect($rules['email'])->toContain('required');
    expect($rules['email'])->toContain('string');
    expect($rules['email'])->toContain('email');
    expect($rules['password'])->toContain('required');
    expect($rules['password'])->toContain('string');
    expect($rules['remember'])->toContain('boolean');
});
