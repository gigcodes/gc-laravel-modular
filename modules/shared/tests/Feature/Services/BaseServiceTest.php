<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Shared\Services\Base\Concretes\BaseService;

uses(RefreshDatabase::class);

// Create concrete implementations for testing
class TestServiceRepository extends \Modules\Shared\Repositories\Base\Concretes\QueryableRepository
{
    public function getModelClass(): string
    {
        return User::class;
    }
}

class TestService extends BaseService
{
    public function __construct(TestServiceRepository $repository)
    {
        $this->setRepository($repository);
    }
}

beforeEach(function () {
    $this->repository = new TestServiceRepository;
    $this->service    = new TestService($this->repository);
});

test('can instantiate base service', function () {
    expect($this->service)->toBeInstanceOf(BaseService::class);
});

test('can create a record through service', function () {
    $data = [
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ];

    $user = $this->service->create($data);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('can find a record through service', function () {
    $user = User::factory()->create();

    $foundUser = $this->service->find($user->id);

    expect($foundUser)->toBeInstanceOf(User::class);
    expect($foundUser->id)->toBe($user->id);
});

test('can update a record through service', function () {
    $user = User::factory()->create();

    $result = $this->service->update($user->id, [
        'name' => 'Updated Name',
    ]);

    expect($result)->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id'   => $user->id,
        'name' => 'Updated Name',
    ]);
});

test('can delete a record through service', function () {
    $user = User::factory()->create();

    $result = $this->service->delete($user->id);

    expect($result)->toBeTrue();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('can get all records through service', function () {
    User::factory()->count(3)->create();

    $users = $this->service->all();

    expect($users)->toHaveCount(3);
});

test('can paginate records through service', function () {
    User::factory()->count(25)->create();

    $paginatedUsers = $this->service->paginate(10);

    expect($paginatedUsers->count())->toBe(10);
    expect($paginatedUsers->total())->toBe(25);
});

test('can use where conditions through service', function () {
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Doe']);
    User::factory()->create(['name' => 'Bob Smith']);

    $users = $this->service->where('name', 'like', '%Doe%');

    expect($users)->toHaveCount(2);
});

test('can use firstWhere through service', function () {
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Doe']);

    $user = $this->service->firstWhere('name', 'Jane Doe');

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Jane Doe');
});

test('can check if record exists through service', function () {
    $user = User::factory()->create();

    expect($this->service->exists($user->id))->toBeTrue();
    expect($this->service->exists(999999))->toBeFalse();
});

test('can count records through service', function () {
    User::factory()->count(5)->create();

    $count = $this->service->count();

    expect($count)->toBe(5);
});

test('service methods are chainable', function () {
    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    // Test that we can chain repository methods through the service
    $user = $this->service->query()
        ->where('name', 'like', '%Doe%')
        ->where('email', 'john@example.com')
        ->first();

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('John Doe');
});

test('can use orderBy through service queries', function () {
    User::factory()->create(['name' => 'Charlie']);
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);

    $users = $this->service->query()
        ->orderBy('name', 'asc')
        ->get();

    expect($users->first()->name)->toBe('Alice');
    expect($users->last()->name)->toBe('Charlie');
});

test('can use findOrFail through service', function () {
    $user = User::factory()->create();

    $foundUser = $this->service->findOrFail($user->id);

    expect($foundUser)->toBeInstanceOf(User::class);
    expect($foundUser->id)->toBe($user->id);
});

test('findOrFail throws exception for non-existent record', function () {
    expect(fn () => $this->service->findOrFail(999999))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('can get repository instance', function () {
    $repository = $this->service->getRepository();

    expect($repository)->toBeInstanceOf(\Modules\Shared\Repositories\Base\Contracts\QueryableRepositoryInterface::class);
});

test('can get filtered results', function () {
    User::factory()->count(3)->create();

    $results = $this->service->getFiltered();

    expect($results)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($results)->toHaveCount(3);
});

test('can get filtered results with specific columns', function () {
    User::factory()->count(2)->create();

    $results = $this->service->getFiltered(['id', 'name']);

    expect($results)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($results)->toHaveCount(2);
});

test('can get current user through service', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $currentUser = $this->service->user();

    expect($currentUser)->toBeInstanceOf(User::class);
    expect($currentUser->id)->toBe($user->id);
});

test('can create record with Data object', function () {
    $data = new class('Test User', 'test@example.com', bcrypt('password')) extends \Spatie\LaravelData\Data
    {
        public function __construct(
            public string $name,
            public string $email,
            public string $password,
        ) {}

        public function toArray(): array
        {
            return [
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => $this->password,
            ];
        }
    };

    $user = $this->service->create($data);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('can update record with Data object', function () {
    $user = User::factory()->create();

    $data = new class('Updated Name') extends \Spatie\LaravelData\Data
    {
        public function __construct(
            public string $name,
        ) {}

        public function toArray(): array
        {
            return [
                'name' => $this->name,
            ];
        }
    };

    $result = $this->service->update($user->id, $data);

    expect($result)->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id'   => $user->id,
        'name' => 'Updated Name',
    ]);
});
