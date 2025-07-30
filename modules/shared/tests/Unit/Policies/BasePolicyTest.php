<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Shared\Policies\BasePolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// Create a concrete implementation for testing
class TestModel extends Model
{
    protected $table = 'users'; // Use existing table for testing

    protected $fillable = ['name', 'email'];
}

class TestPolicy extends BasePolicy
{
    protected string $model = TestModel::class;
}

beforeEach(function () {
    // Create necessary roles and permissions for testing
    Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
    Permission::create(['name' => 'view any testmodel', 'guard_name' => 'web']);
    Permission::create(['name' => 'view testmodel', 'guard_name' => 'web']);
    Permission::create(['name' => 'create testmodel', 'guard_name' => 'web']);
    Permission::create(['name' => 'update testmodel', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete testmodel', 'guard_name' => 'web']);
    Permission::create(['name' => 'restore testmodel', 'guard_name' => 'web']);
    Permission::create(['name' => 'force delete testmodel', 'guard_name' => 'web']);
});

test('base policy before method allows super admin', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $policy = new TestPolicy;
    $result = $policy->before($user, 'view');

    expect($result)->toBeTrue();
});

test('base policy before method returns null for regular users', function () {
    $user = User::factory()->create();

    $policy = new TestPolicy;
    $result = $policy->before($user, 'view');

    expect($result)->toBeNull();
});

test('base policy before method returns null for guest users', function () {
    $policy = new TestPolicy;
    $result = $policy->before(null, 'view');

    expect($result)->toBeNull();
});

test('base policy get model name uses model property', function () {
    $policy     = new TestPolicy;
    $reflection = new ReflectionClass($policy);
    $method     = $reflection->getMethod('getModelName');
    $method->setAccessible(true);

    $result = $method->invoke($policy);

    expect($result)->toBe('testmodel');
});

test('base policy get model name derives from policy class name', function () {
    $policy     = new class extends BasePolicy {};
    $reflection = new ReflectionClass($policy);
    $method     = $reflection->getMethod('getModelName');
    $method->setAccessible(true);

    $result = $method->invoke($policy);

    // Since it's an anonymous class, the result will contain some generated name
    expect($result)->toBeString();
    expect(strlen($result))->toBeGreaterThan(0);
});

test('base policy check permission allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view testmodel');

    $policy     = new TestPolicy;
    $reflection = new ReflectionClass($policy);
    $method     = $reflection->getMethod('checkPermission');
    $method->setAccessible(true);

    $result = $method->invoke($policy, 'view', $user);

    expect($result->allowed())->toBeTrue();
});

test('base policy check permission denies user without permission', function () {
    $user = User::factory()->create();

    $policy     = new TestPolicy;
    $reflection = new ReflectionClass($policy);
    $method     = $reflection->getMethod('checkPermission');
    $method->setAccessible(true);

    $result = $method->invoke($policy, 'view', $user);

    expect($result->denied())->toBeTrue();
});

test('base policy view any allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view any testmodel');

    $policy = new TestPolicy;
    $result = $policy->viewAny($user);

    expect($result->allowed())->toBeTrue();
});

test('base policy view any denies guest user', function () {
    $policy = new TestPolicy;
    $result = $policy->viewAny(null);

    expect($result->denied())->toBeTrue();
});

test('base policy view allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view testmodel');
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->view($user, $model);

    expect($result->allowed())->toBeTrue();
});

test('base policy view denies guest user', function () {
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->view(null, $model);

    expect($result->denied())->toBeTrue();
});

test('base policy create allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('create testmodel');

    $policy = new TestPolicy;
    $result = $policy->create($user);

    expect($result->allowed())->toBeTrue();
});

test('base policy create denies guest user', function () {
    $policy = new TestPolicy;
    $result = $policy->create(null);

    expect($result->denied())->toBeTrue();
});

test('base policy update allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('update testmodel');
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->update($user, $model);

    expect($result->allowed())->toBeTrue();
});

test('base policy update denies guest user', function () {
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->update(null, $model);

    expect($result->denied())->toBeTrue();
});

test('base policy delete allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('delete testmodel');
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->delete($user, $model);

    expect($result->allowed())->toBeTrue();
});

test('base policy delete denies guest user', function () {
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->delete(null, $model);

    expect($result->denied())->toBeTrue();
});

test('base policy restore allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('restore testmodel');
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->restore($user, $model);

    expect($result->allowed())->toBeTrue();
});

test('base policy restore denies guest user', function () {
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->restore(null, $model);

    expect($result->denied())->toBeTrue();
});

test('base policy restore any allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('restore testmodel');

    $policy = new TestPolicy;
    $result = $policy->restoreAny($user);

    expect($result->allowed())->toBeTrue();
});

test('base policy restore any denies guest user', function () {
    $policy = new TestPolicy;
    $result = $policy->restoreAny(null);

    expect($result->denied())->toBeTrue();
});

test('base policy force delete allows user with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('force delete testmodel');
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->forceDelete($user, $model);

    expect($result->allowed())->toBeTrue();
});

test('base policy force delete denies guest user', function () {
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->forceDelete(null, $model);

    expect($result->denied())->toBeTrue();
});

test('base policy magic call method handles spaced methods', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view any testmodel');

    $policy = new TestPolicy;
    $result = $policy->{'view any'}($user);

    expect($result->allowed())->toBeTrue();
});

test('base policy magic call method handles force delete', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('force delete testmodel');
    $model = new TestModel;

    $policy = new TestPolicy;
    $result = $policy->{'force delete'}($user, $model);

    expect($result->allowed())->toBeTrue();
});

test('base policy magic call method handles custom permissions', function () {
    $user = User::factory()->create();
    Permission::create(['name' => 'custom testmodel', 'guard_name' => 'web']);
    $user->givePermissionTo('custom testmodel');

    $policy = new TestPolicy;
    $result = $policy->custom($user);

    expect($result->allowed())->toBeTrue();
});

test('base policy magic call method throws exception for invalid method', function () {
    $policy = new TestPolicy;

    expect(fn () => $policy->invalidMethod())
        ->toThrow(\BadMethodCallException::class, 'Method invalidMethod does not exist.');
});

test('base policy get model name uses acl config', function () {
    config(['acl.models' => [
        TestModel::class => ['permission_name' => 'custom_model'],
    ]]);

    $policy     = new TestPolicy;
    $reflection = new ReflectionClass($policy);
    $method     = $reflection->getMethod('getModelName');
    $method->setAccessible(true);

    $result = $method->invoke($policy);

    expect($result)->toBe('custom_model');
});
