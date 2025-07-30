<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\UserRepository;
use Modules\Auth\Services\UserService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->userRepository = app(UserRepository::class);
    $this->userService    = app(UserService::class);
});

test('can create a user through service', function () {
    $userData = [
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ];

    $user = $this->userService->create($userData);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('can find a user by id', function () {
    $user = User::factory()->create();

    $foundUser = $this->userService->find($user->id);

    expect($foundUser)->toBeInstanceOf(User::class);
    expect($foundUser->id)->toBe($user->id);
});

test('can update a user', function () {
    $user = User::factory()->create();

    $result = $this->userService->update($user->id, [
        'name' => 'Updated Name',
    ]);

    expect($result)->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id'   => $user->id,
        'name' => 'Updated Name',
    ]);
});

test('can delete a user', function () {
    $user = User::factory()->create();

    $result = $this->userService->delete($user->id);

    expect($result)->toBeTrue();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('can get all users', function () {
    User::factory()->count(3)->create();

    $users = $this->userService->all();

    expect($users)->toHaveCount(3);
    expect($users->first())->toBeInstanceOf(User::class);
});

test('can get paginated users', function () {
    User::factory()->count(25)->create();

    $paginatedUsers = $this->userService->paginate(10);

    expect($paginatedUsers->count())->toBe(10);
    expect($paginatedUsers->total())->toBe(25);
    expect($paginatedUsers->currentPage())->toBe(1);
    expect($paginatedUsers->lastPage())->toBe(3);
});

test('can find user by email using where method', function () {
    $user = User::factory()->create([
        'email' => 'specific@example.com',
    ]);

    $foundUsers = $this->userService->where('email', 'specific@example.com');

    expect($foundUsers)->toHaveCount(1);
    expect($foundUsers->first()->id)->toBe($user->id);
});

test('can find first user matching criteria', function () {
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'John Smith']);

    $user = $this->userService->firstWhere('name', 'like', 'John%');

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBeIn(['John Doe', 'John Smith']);
});

test('returns null when finding non-existent user', function () {
    $user = $this->userService->find(999999);

    expect($user)->toBeNull();
});
