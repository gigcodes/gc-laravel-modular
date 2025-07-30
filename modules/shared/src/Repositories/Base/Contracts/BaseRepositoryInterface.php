<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories\Base\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template TModel of Model
 */
interface BaseRepositoryInterface
{
    /**
     * Set new model. It can be: bare model, QueryBuilder, Relation,
     */
    public function setModel(Model|Builder|Relation|string $entity): void;

    public function getModelClass(): string;

    /**
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * @param array<string> $columns
     * @param array<string> $relations
     * @param array<string> $appends
     *
     * @return TModel|null
     */
    public function findById(string|int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model;

    /**
     * Find resource by id (alias for findById)
     *
     * @param array<string> $columns
     *
     * @return TModel|null
     */
    public function find(string|int $id, array $columns = ['*']): ?Model;

    /**
     * Find resource or fail
     *
     * @param array<string> $columns
     *
     * @return TModel
     */
    public function findOrFail(string|int $id, array $columns = ['*']): Model;

    /**
     * @param array<string, mixed> $payload
     *
     * @return TModel
     */
    public function create(array $payload): Model;

    /**
     * Create a new model instance without saving it
     *
     * @param array<string, mixed> $attributes
     *
     * @return TModel
     */
    public function make(array $attributes = []): Model;

    /**
     * @param array<string, mixed> $payload
     */
    public function update(string|int $id, array $payload): bool;

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     *
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Delete a model by ID
     */
    public function delete(string|int $id): bool;

    /**
     * Force delete a model by ID (bypasses soft deletes)
     */
    public function forceDelete(string|int $id): bool;

    /**
     * Restore a soft deleted model by ID
     */
    public function restore(string|int $id): bool;

    /**
     * @param array<string> $columns
     * @param array<string> $relations
     *
     * @return TModel|null
     */
    public function findBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find resource by field (alias for findBy)
     *
     * @param array<string> $columns
     *
     * @return TModel|null
     */
    public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model;

    /**
     * @return Collection<int, TModel>
     */
    public function getBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get model instance
     *
     * @return TModel
     */
    public function getModel(): Model;

    /**
     * Find records by conditions.
     *
     * @param array<string, mixed> $conditions
     * @param array<string>        $columns
     *
     * @return Collection<int, TModel>
     */
    public function getByConditions(array $conditions, array $columns = ['*']): Collection;

    /**
     * Find first record by conditions.
     *
     * @param array<string, mixed> $conditions
     * @param array<string>        $columns
     *
     * @return TModel|null
     */
    public function findByConditions(array $conditions, array $columns = ['*']): ?Model;

    public function findOrFailByConditions(array $conditions, array $columns = ['*']): ?Model;

    /**
     * Get query builder by conditions.
     *
     * @param array<string, mixed> $conditions
     *
     * @return Builder<TModel>
     */
    public function queryByConditions(array $conditions): Builder;
}
