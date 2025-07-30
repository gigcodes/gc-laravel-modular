<?php

use Modules\Auth\DTO\ForgotPasswordData;

test('can create forgot password data with email', function () {
    $data = new ForgotPasswordData(
        email: 'user@example.com'
    );

    expect($data->email)->toBe('user@example.com');
});

test('can create forgot password data from array', function () {
    $array = [
        'email' => 'test@example.com',
    ];

    $data = ForgotPasswordData::from($array);

    expect($data->email)->toBe('test@example.com');
});

test('can convert forgot password data to array', function () {
    $data = new ForgotPasswordData(
        email: 'user@example.com'
    );

    $array = $data->toArray();

    expect($array)->toBe([
        'email' => 'user@example.com',
    ]);
});

test('forgot password data has correct validation rules', function () {
    $rules = ForgotPasswordData::rules();

    expect($rules)->toHaveKey('email');
    expect($rules['email'])->toContain('required');
    expect($rules['email'])->toContain('string');
    expect($rules['email'])->toContain('email');
});
