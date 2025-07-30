<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Shared\Repositories\Base\Concretes\QueryableRepository;

uses(RefreshDatabase::class);

// Create a concrete implementation for testing
class TestQueryableRepository extends QueryableRepository
{
    public function getModelClass(): string
    {
        return User::class;
    }
}

beforeEach(function () {
    $this->repository = new TestQueryableRepository;
});

test('can paginate filtered results', function () {
    User::factory()->count(25)->create();

    $result = $this->repository->paginateFiltered(10);

    expect($result->count())->toBe(10);
    expect($result->total())->toBe(25);
});

test('paginates filtered results with per_page from request', function () {
    User::factory()->count(20)->create();

    // Simulate request with per_page parameter
    request()->merge(['per_page' => 5]);

    $result = $this->repository->paginateFiltered(10);

    expect($result->count())->toBe(5);
    expect($result->total())->toBe(20);
});

test('paginates filtered results with null per_page from request uses default', function () {
    User::factory()->count(15)->create();

    // Simulate request with null per_page parameter
    request()->merge(['per_page' => null]);

    $result = $this->repository->paginateFiltered(7);

    expect($result->count())->toBe(7);
    expect($result->total())->toBe(15);
});

test('can get filtered results', function () {
    User::factory()->count(3)->create();

    $results = $this->repository->getFiltered();

    expect($results)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($results)->toHaveCount(3);
});

test('can get filtered results with specific columns', function () {
    User::factory()->count(2)->create();

    $results = $this->repository->getFiltered(['id', 'name']);

    expect($results)->toHaveCount(2);
    foreach ($results as $result) {
        expect($result->toArray())->toHaveKeys(['id', 'name']);
        expect($result->toArray())->not->toHaveKey('email');
    }
});
