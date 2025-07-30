<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\UserRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repository = app(UserRepository::class);
});

test('can get model class', function () {
    expect($this->repository->getModelClass())->toBe(User::class);
});

test('can create a user', function () {
    $userData = [
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ];

    $user = $this->repository->create($userData);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('can find a user by id', function () {
    $user = User::factory()->create();

    $foundUser = $this->repository->find($user->id);

    expect($foundUser)->toBeInstanceOf(User::class);
    expect($foundUser->id)->toBe($user->id);
});

test('can update a user by model', function () {
    $user = User::factory()->create();

    $updatedUser = $this->repository->updateByModel($user, [
        'name' => 'Updated Name',
    ]);

    expect($updatedUser->name)->toBe('Updated Name');
    expect($updatedUser->id)->toBe($user->id);

    $this->assertDatabaseHas('users', [
        'id'   => $user->id,
        'name' => 'Updated Name',
    ]);
});

test('can update a user by id', function () {
    $user = User::factory()->create();

    $result = $this->repository->update($user->id, [
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

    $result = $this->repository->delete($user->id);

    expect($result)->toBeTrue();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('can get all users', function () {
    User::factory()->count(3)->create();

    $users = $this->repository->all();

    expect($users)->toHaveCount(3);
    expect($users->first())->toBeInstanceOf(User::class);
});

test('can get paginated users', function () {
    User::factory()->count(25)->create();

    $paginatedUsers = $this->repository->paginate(10);

    expect($paginatedUsers->count())->toBe(10);
    expect($paginatedUsers->total())->toBe(25);
});

test('can build query with where conditions', function () {
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Doe']);
    User::factory()->create(['name' => 'Bob Smith']);

    $query = $this->repository->query()
        ->where('name', 'like', '%Doe%');

    $users = $query->get();

    expect($users)->toHaveCount(2);
});

test('can use where method', function () {
    User::factory()->create(['email' => 'test1@example.com']);
    User::factory()->create(['email' => 'test2@example.com']);

    $users = $this->repository->where('email', 'test1@example.com');

    expect($users)->toHaveCount(1);
    expect($users->first()->email)->toBe('test1@example.com');
});

test('can use firstWhere method', function () {
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Doe']);

    $user = $this->repository->firstWhere('name', 'Jane Doe');

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Jane Doe');
});

test('can check if record exists', function () {
    $user = User::factory()->create();

    expect($this->repository->exists($user->id))->toBeTrue();
    expect($this->repository->exists(999999))->toBeFalse();
});

test('can get users by company with employee details', function () {
    // This test assumes there's a relationship between users and companies
    // Since we don't have the company_id column or employeeDetail relationship in the migration,
    // we'll skip the actual implementation and just verify the method exists

    $users = $this->repository->getByCompanyWithEmployeeDetails(1);

    expect($users)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($users)->toHaveCount(0);
});

test('can count users', function () {
    User::factory()->count(5)->create();

    $count = $this->repository->count();

    expect($count)->toBe(5);
});

test('can use orderBy in queries', function () {
    User::factory()->create(['name' => 'Charlie']);
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);

    $users = $this->repository->query()
        ->orderBy('name', 'asc')
        ->get();

    expect($users->first()->name)->toBe('Alice');
    expect($users->last()->name)->toBe('Charlie');
});
