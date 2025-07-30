<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories\Base\Concretes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Modules\Shared\Repositories\Base\Contracts\BaseRepositoryInterface;

/**
 * Base Repository providing common database operations for Eloquent models.
 *
 * This abstract class implements common repository pattern methods with full
 * generic type support for better IDE autocompletion and static analysis.
 *
 * @template TModel of Model
 *
 * @implements BaseRepositoryInterface<TModel>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /** @var Builder<TModel> */
    protected Builder $model;

    public function __construct()
    {
        $this->setModel($this->getModelClass());
    }

    /**
     * Set new model. It can be: bare model, QueryBuilder, Relation,
     */
    public function setModel(Model|Builder|Relation|string $entity): void
    {
        if (is_a($entity, Model::class) || is_subclass_of($entity, Model::class)) {
            /** @var class-string<TModel> $entity */
            /** @var Builder<TModel> $query */
            $query       = $entity::query();
            $this->model = $query;
        } elseif (
            is_a($entity, Builder::class) ||
            is_subclass_of($entity, Builder::class)
        ) {
            /** @var Builder<TModel> $entity */
            $this->model = $entity;
        } elseif (
            is_a($entity, Relation::class) ||
            is_subclass_of($entity, Relation::class)
        ) {
            /** @var Builder<TModel> $query */
            $query       = $entity->getQuery();
            $this->model = $query;
        } elseif (is_string($entity)) {
            /** @var class-string<TModel> $entity */
            $this->model = App::make($entity)->query();
        } else {
            throw new InvalidArgumentException('Invalid entity type');
        }
    }

    /**
     * Specify Model class name
     */
    abstract public function getModelClass(): string;

    /**
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return (clone $this->model)->with($relations)->get($columns);
    }

    /**
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return (clone $this->model)->with($relations)->paginate($perPage, $columns);
    }

    /**
     * @return TModel|null
     */
    public function findById(string|int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $query = (clone $this->model)->with($relations)->select($columns);

        return $query->find($id)?->append($appends);
    }

    /**
     * @return TModel|null
     */
    public function find(string|int $id, array $columns = ['*']): ?Model
    {
        return (clone $this->model)->find($id, $columns);
    }

    /**
     * @return TModel
     */
    public function findOrFail(string|int $id, array $columns = ['*']): Model
    {
        return (clone $this->model)->findOrFail($id, $columns);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return TModel
     */
    public function create(array $payload): Model
    {
        return (clone $this->model)->create($payload);
    }

    public function update(string|int $id, array $payload): bool
    {
        return (bool) (clone $this->model)->where('id', $id)->update($payload);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     *
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return (clone $this->model)->updateOrCreate($attributes, $values);
    }

    public function delete(string|int $id): bool
    {
        return (bool) (clone $this->model)->where('id', $id)->delete();
    }

    public function forceDelete(string|int $id): bool
    {
        return (bool) (clone $this->model)->where('id', $id)->forceDelete();
    }

    public function restore(string|int $id): bool
    {
        /** @var Builder<TModel> $query */
        $query = (clone $this->model)->where('id', $id);

        // Check if the model uses SoftDeletes trait
        if (method_exists($query->getModel(), 'restore')) {
            /** @phpstan-ignore-next-line */
            return (bool) $query->restore();
        }

        return false;
    }

    /**
     * @return TModel|null
     */
    public function findBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): ?Model
    {
        return (clone $this->model)->with($relations)->select($columns)->where($column, $value)->first();
    }

    /**
     * @return TModel|null
     */
    public function findByField(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return (clone $this->model)->where($field, $value)->first($columns);
    }

    /**
     * @return Collection<int, TModel>
     */
    public function getBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): Collection
    {
        return (clone $this->model)->with($relations)->select($columns)->where($column, $value)->get();
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return TModel
     */
    public function make(array $attributes = []): Model
    {
        return new ($this->getModelClass())($attributes);
    }

    /**
     * @return TModel
     */
    public function getModel(): Model
    {
        return $this->model->getModel();
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string>        $columns
     *
     * @return Collection<int, TModel>
     */
    public function getByConditions(array $conditions, array $columns = ['*']): Collection
    {
        return $this->queryByConditions($conditions)->get($columns);
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string>        $columns
     *
     * @return TModel|null
     */
    public function findByConditions(array $conditions, array $columns = ['*']): ?Model
    {
        return $this->queryByConditions($conditions)->first($columns);
    }

    public function findOrFailByConditions(array $conditions, array $columns = ['*']): ?Model
    {
        return $this->queryByConditions($conditions)->firstOrFail($columns);
    }

    /**
     * @param array<string, mixed> $conditions
     *
     * @return Builder<TModel>
     */
    public function queryByConditions(array $conditions): Builder
    {
        /** @var Builder<TModel> */
        return (clone $this->model)->where($conditions);
    }

    /**
     * Get resources by where condition
     *
     * @return Collection<int, TModel>
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): Collection
    {
        return (clone $this->model)->where($column, $operator, $value, $boolean)->get();
    }

    /**
     * Get first resource by where condition
     *
     * @return TModel|null
     */
    public function firstWhere(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): ?Model
    {
        return (clone $this->model)->where($column, $operator, $value, $boolean)->first();
    }

    public function count(): int
    {
        return (clone $this->model)->count();
    }

    public function exists(string|int $id): bool
    {
        return (clone $this->model)->where('id', $id)->exists();
    }
}
