<?php

use Modules\Auth\DTO\ResetPasswordData;

test('can create reset password data with all fields', function () {
    $data = new ResetPasswordData(
        token: 'reset-token-123',
        email: 'user@example.com',
        password: 'new-password',
        password_confirmation: 'new-password'
    );

    expect($data->token)->toBe('reset-token-123');
    expect($data->email)->toBe('user@example.com');
    expect($data->password)->toBe('new-password');
    expect($data->password_confirmation)->toBe('new-password');
});

test('can create reset password data from array', function () {
    $array = [
        'token'                 => 'token-abc',
        'email'                 => 'test@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ];

    $data = ResetPasswordData::from($array);

    expect($data->token)->toBe('token-abc');
    expect($data->email)->toBe('test@example.com');
    expect($data->password)->toBe('secret123');
    expect($data->password_confirmation)->toBe('secret123');
});

test('can convert reset password data to array', function () {
    $data = new ResetPasswordData(
        token: 'token-xyz',
        email: 'user@example.com',
        password: 'password123',
        password_confirmation: 'password123'
    );

    $array = $data->toArray();

    expect($array)->toBe([
        'token'                 => 'token-xyz',
        'email'                 => 'user@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);
});

test('reset password data has correct validation rules', function () {
    $rules = ResetPasswordData::rules();

    expect($rules)->toHaveKey('token');
    expect($rules)->toHaveKey('email');
    expect($rules)->toHaveKey('password');
    expect($rules)->toHaveKey('password_confirmation');

    expect($rules['token'])->toContain('required');
    expect($rules['email'])->toContain('required');
    expect($rules['email'])->toContain('email');
    expect($rules['password'])->toContain('required');
    expect($rules['password'])->toContain('confirmed');
    expect($rules['password'])->toContain('min:8');
    expect($rules['password_confirmation'])->toContain('required');
});
