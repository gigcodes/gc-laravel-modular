<?php

declare(strict_types=1);

namespace Modules\Shared\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * The model class this policy is for
     */
    protected string $model;

    /**
     * Perform pre-authorization checks.
     */
    public function before(?User $user, string $ability): ?bool
    {
        if (! $user) {
            return null;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Get the model name for permission checking
     */
    protected function getModelName(): string
    {
        if (isset($this->model)) {
            // Check if there's a custom permission name in ACL config
            $aclConfig = config('acl.models', []);

            if (isset($aclConfig[$this->model]['permission_name'])) {
                return $aclConfig[$this->model]['permission_name'];
            }

            return Str::lower(Str::singular(class_basename($this->model)));
        }

        // Fallback: derive from policy class name
        $policyName = class_basename(static::class);

        return Str::lower(Str::singular(str_replace('Policy', '', $policyName)));
    }

    /**
     * Check if user has the given permission for this model
     */
    protected function checkPermission(string $action, User $user): Response
    {
        $modelName  = $this->getModelName();
        $permission = "{$action} {$modelName}";

        return $user->can($permission)
            ? Response::allow()
            : Response::deny();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): Response
    {
        if (! $user) {
            return Response::deny();
        }

        return $this->checkPermission('view any', $user);
    }

    /**
     * Handle spaced authorization format dynamically
     */
    public function __call(string $method, array $arguments): Response
    {
        // Convert spaced method names to camelCase
        $spacedMethods = [
            'view any'     => 'viewAny',
            'force delete' => 'forceDelete',
        ];

        if (isset($spacedMethods[$method])) {
            return $this->{$spacedMethods[$method]}(...$arguments);
        }

        // If it's a permission check, try to handle it
        if (count($arguments) >= 1 && $arguments[0] instanceof User) {
            return $this->checkPermission($method, $arguments[0]);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Model $model): Response
    {
        if (! $user) {
            return Response::deny();
        }

        return $this->checkPermission('view', $user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): Response
    {
        if (! $user) {
            return Response::deny();
        }

        return $this->checkPermission('create', $user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, Model $model): Response
    {
        if (! $user) {
            return Response::deny();
        }

        return $this->checkPermission('update', $user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, Model $model): Response
    {
        if (! $user) {
            return Response::deny();
        }

        return $this->checkPermission('delete', $user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user, Model $model): Response
    {
        if (! $user) {
            return Response::deny();
        }

        return $this->checkPermission('restore', $user);
    }

    /**
     * Determine whether the user can restore any model.
     */
    public function restoreAny(?User $user): Response
    {
        if (! $user) {
            return Response::deny();
        }

        return $this->checkPermission('restore', $user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user, Model $model): Response
    {
        if (! $user) {
            return Response::deny();
        }

        return $this->checkPermission('force delete', $user);
    }
}
