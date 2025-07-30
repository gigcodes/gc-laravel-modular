<?php

use Modules\Auth\DTO\TokenData;

test('can create token data with required fields', function () {
    $tokenData = new TokenData(
        access_token: 'test-access-token'
    );

    expect($tokenData->access_token)->toBe('test-access-token');
    expect($tokenData->token_type)->toBe('Bearer');
    expect($tokenData->expires_in)->toBeNull();
});

test('can create token data with all fields', function () {
    $tokenData = new TokenData(
        access_token: 'test-access-token',
        token_type: 'Custom',
        expires_in: 3600
    );

    expect($tokenData->access_token)->toBe('test-access-token');
    expect($tokenData->token_type)->toBe('Custom');
    expect($tokenData->expires_in)->toBe(3600);
});

test('can create token data from array', function () {
    $data = [
        'access_token' => 'test-token',
        'token_type'   => 'Bearer',
        'expires_in'   => 7200,
    ];

    $tokenData = TokenData::from($data);

    expect($tokenData->access_token)->toBe('test-token');
    expect($tokenData->token_type)->toBe('Bearer');
    expect($tokenData->expires_in)->toBe(7200);
});

test('can convert token data to array', function () {
    $tokenData = new TokenData(
        access_token: 'test-token',
        token_type: 'Bearer',
        expires_in: 3600
    );

    $array = $tokenData->toArray();

    expect($array)->toBe([
        'access_token' => 'test-token',
        'token_type'   => 'Bearer',
        'expires_in'   => 3600,
    ]);
});
