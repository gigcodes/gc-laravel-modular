<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Shared\Repositories\Base\Concretes\BaseRepository;

uses(RefreshDatabase::class);

// Create a concrete implementation for testing
class TestRepository extends BaseRepository
{
    public function getModelClass(): string
    {
        return User::class;
    }
}

beforeEach(function () {
    $this->repository = new TestRepository;
});

test('can instantiate base repository', function () {
    expect($this->repository)->toBeInstanceOf(BaseRepository::class);
});

test('can create a record', function () {
    $data = [
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ];

    $user = $this->repository->create($data);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('can find a record by id', function () {
    $user = User::factory()->create();

    $foundUser = $this->repository->find($user->id);

    expect($foundUser)->toBeInstanceOf(User::class);
    expect($foundUser->id)->toBe($user->id);
});

test('returns null when finding non-existent record', function () {
    $result = $this->repository->find(999999);

    expect($result)->toBeNull();
});

test('can update a record', function () {
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

test('can delete a record', function () {
    $user = User::factory()->create();

    $result = $this->repository->delete($user->id);

    expect($result)->toBeTrue();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('can get all records', function () {
    User::factory()->count(3)->create();

    $users = $this->repository->all();

    expect($users)->toHaveCount(3);
    expect($users->first())->toBeInstanceOf(User::class);
});

test('can get all records with specific columns', function () {
    User::factory()->count(2)->create();

    $users = $this->repository->all(['id', 'name']);

    expect($users)->toHaveCount(2);
    expect($users->first()->toArray())->toHaveKeys(['id', 'name']);
    expect($users->first()->toArray())->not->toHaveKey('email');
});

test('can paginate records', function () {
    User::factory()->count(25)->create();

    $paginatedUsers = $this->repository->paginate(10);

    expect($paginatedUsers->count())->toBe(10);
    expect($paginatedUsers->total())->toBe(25);
    expect($paginatedUsers->currentPage())->toBe(1);
    expect($paginatedUsers->lastPage())->toBe(3);
});

test('can paginate with custom columns', function () {
    User::factory()->count(15)->create();

    $paginatedUsers = $this->repository->paginate(5, ['id', 'name']);

    expect($paginatedUsers->count())->toBe(5);
    expect($paginatedUsers->first()->toArray())->toHaveKeys(['id', 'name']);
    expect($paginatedUsers->first()->toArray())->not->toHaveKey('email');
});

test('can check if record exists', function () {
    $user = User::factory()->create();

    expect($this->repository->exists($user->id))->toBeTrue();
    expect($this->repository->exists(999999))->toBeFalse();
});

test('can count records', function () {
    User::factory()->count(5)->create();

    $count = $this->repository->count();

    expect($count)->toBe(5);
});

test('can use findOrFail', function () {
    $user = User::factory()->create();

    $foundUser = $this->repository->findOrFail($user->id);

    expect($foundUser)->toBeInstanceOf(User::class);
    expect($foundUser->id)->toBe($user->id);
});

test('findOrFail throws exception for non-existent record', function () {
    expect(fn () => $this->repository->findOrFail(999999))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('can batch create records', function () {
    $user1 = $this->repository->create([
        'name'     => 'User 1',
        'email'    => 'user1@example.com',
        'password' => bcrypt('password'),
    ]);

    $user2 = $this->repository->create([
        'name'     => 'User 2',
        'email'    => 'user2@example.com',
        'password' => bcrypt('password'),
    ]);

    expect($user1)->toBeInstanceOf(User::class);
    expect($user2)->toBeInstanceOf(User::class);

    $this->assertDatabaseHas('users', ['email' => 'user1@example.com']);
    $this->assertDatabaseHas('users', ['email' => 'user2@example.com']);
});

test('can perform bulk update', function () {
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        $this->repository->update($user->id, [
            'name' => 'Bulk Updated',
        ]);
    }

    foreach ($users as $user) {
        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => 'Bulk Updated',
        ]);
    }
});

test('can delete multiple records', function () {
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        $result = $this->repository->delete($user->id);
        expect($result)->toBeTrue();
    }

    foreach ($users as $user) {
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
});

test('can use advanced where with operators', function () {
    User::factory()->create(['name' => 'John', 'created_at' => now()->subDays(5)]);
    User::factory()->create(['name' => 'Jane', 'created_at' => now()->subDays(2)]);
    User::factory()->create(['name' => 'Bob', 'created_at' => now()->subDays(10)]);

    $users = $this->repository->where('created_at', '>', now()->subDays(7));

    expect($users)->toHaveCount(2);
});

test('handles empty results gracefully', function () {
    $users = $this->repository->where('name', 'NonExistentUser');

    expect($users)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($users)->toHaveCount(0);
});

test('can find by id with relations and appends', function () {
    $user = User::factory()->create();

    $result = $this->repository->findById($user->id, ['*'], [], ['custom_field']);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('can find by id returns null for non-existent', function () {
    $result = $this->repository->findById(999, ['*'], [], ['custom_field']);

    expect($result)->toBeNull();
});

test('can use updateOrCreate method', function () {
    $attributes = ['email' => 'unique@example.com'];
    $values     = ['name' => 'Updated User', 'password' => bcrypt('password')];

    $user = $this->repository->updateOrCreate($attributes, $values);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->email)->toBe('unique@example.com');
    expect($user->name)->toBe('Updated User');

    // Test update case
    $updatedUser = $this->repository->updateOrCreate($attributes, ['name' => 'Modified User']);
    expect($updatedUser->id)->toBe($user->id);
    expect($updatedUser->name)->toBe('Modified User');
});

test('can force delete records', function () {
    $user = User::factory()->create();

    $result = $this->repository->forceDelete($user->id);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('can restore soft deleted records if model supports it', function () {
    $user = User::factory()->create();

    // Since User model doesn't use SoftDeletes, this should return false
    $result = $this->repository->restore($user->id);

    expect($result)->toBeFalse();
});

test('can find by field', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $result = $this->repository->findBy('email', 'test@example.com');

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('can find by field with relations', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $result = $this->repository->findBy('email', 'test@example.com', ['*'], []);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('can find by field using findByField method', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $result = $this->repository->findByField('email', 'test@example.com');

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('can get multiple records by field', function () {
    User::factory()->count(3)->create(['name' => 'Test User']);

    $results = $this->repository->getBy('name', 'Test User');

    expect($results)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($results)->toHaveCount(3);
});

test('can make new model instance', function () {
    $attributes = ['name' => 'Test User', 'email' => 'test@example.com'];

    $user = $this->repository->make($attributes);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->exists)->toBeFalse(); // Not saved to database
});

test('can get model instance', function () {
    $model = $this->repository->getModel();

    expect($model)->toBeInstanceOf(User::class);
});

test('can get records by conditions', function () {
    $user1 = User::factory()->create(['name' => 'John', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $results = $this->repository->getByConditions(['name' => 'John']);

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($user1->id);
});

test('can find record by conditions', function () {
    $user = User::factory()->create(['name' => 'John', 'email' => 'john@example.com']);

    $result = $this->repository->findByConditions(['name' => 'John']);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('can find or fail by conditions', function () {
    $user = User::factory()->create(['name' => 'John']);

    $result = $this->repository->findOrFailByConditions(['name' => 'John']);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('find or fail by conditions throws exception for non-existent', function () {
    expect(fn () => $this->repository->findOrFailByConditions(['name' => 'NonExistent']))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('can query by conditions', function () {
    User::factory()->create(['name' => 'John']);

    $query = $this->repository->queryByConditions(['name' => 'John']);

    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
    $results = $query->get();
    expect($results)->toHaveCount(1);
});

test('repository can be instantiated with builder instance', function () {
    // Create a concrete repository class that can be instantiated for testing
    $concreteRepo = new class(User::query()) extends \Modules\Shared\Repositories\Base\Concretes\BaseRepository
    {
        public function getModelClass(): string
        {
            return User::class;
        }
    };

    $user   = User::factory()->create();
    $result = $concreteRepo->find($user->id);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('repository can be instantiated with string class name', function () {
    // Create a concrete repository class that can be instantiated for testing
    $concreteRepo = new class(User::class) extends \Modules\Shared\Repositories\Base\Concretes\BaseRepository
    {
        public function getModelClass(): string
        {
            return User::class;
        }
    };

    $user   = User::factory()->create();
    $result = $concreteRepo->find($user->id);

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toBe($user->id);
});

test('restore method returns false for models without soft deletes', function () {
    $user = User::factory()->create();

    // User model doesn't use SoftDeletes by default
    $result = $this->repository->restore($user->id);

    expect($result)->toBeFalse();
});
