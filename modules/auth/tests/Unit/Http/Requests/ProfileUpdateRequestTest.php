<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rule;
use Modules\Auth\Http\Requests\ProfileUpdateRequest;
use Modules\Auth\Models\User;

uses(RefreshDatabase::class);

test('profile update request has correct validation rules', function () {
    $user = User::factory()->create();

    $request = new ProfileUpdateRequest;
    $request->setUserResolver(fn () => $user);

    $rules = $request->rules();

    expect($rules['name'])->toEqual(['required', 'string', 'max:255']);
    expect($rules['email'])->toContain('required', 'string', 'lowercase', 'email', 'max:255');

    // Check if unique rule exists
    $hasUniqueRule = false;
    foreach ($rules['email'] as $rule) {
        if ($rule instanceof \Illuminate\Validation\Rules\Unique) {
            $hasUniqueRule = true;
            break;
        }
    }
    expect($hasUniqueRule)->toBeTrue();
});

test('profile update request validates name field', function () {
    $user = User::factory()->create();

    $request = new ProfileUpdateRequest;
    $request->setUserResolver(fn () => $user);

    $rules = $request->rules();

    expect($rules)->toHaveKey('name');
    expect($rules['name'])->toContain('required');
    expect($rules['name'])->toContain('string');
    expect($rules['name'])->toContain('max:255');
});

test('profile update request validates email field with unique rule', function () {
    $user      = User::factory()->create();
    $otherUser = User::factory()->create();

    $request = new ProfileUpdateRequest;
    $request->setUserResolver(fn () => $user);

    $rules = $request->rules();

    expect($rules)->toHaveKey('email');
    expect($rules['email'])->toContain('required');
    expect($rules['email'])->toContain('string');
    expect($rules['email'])->toContain('lowercase');
    expect($rules['email'])->toContain('email');
    expect($rules['email'])->toContain('max:255');
});
