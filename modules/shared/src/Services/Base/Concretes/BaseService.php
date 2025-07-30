<?php

declare(strict_types=1);

namespace Modules\Shared\Services\Base\Concretes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\User;
use Modules\Shared\Repositories\Base\Contracts\QueryableRepositoryInterface;
use Modules\Shared\Services\Base\Contracts\BaseServiceInterface;
use Spatie\LaravelData\Data;

/**
 * Base Service providing common service operations.
 *
 * @template TModel of Model
 * @template TRepository of QueryableRepositoryInterface
 */
abstract class BaseService implements BaseServiceInterface
{
    /** @var TRepository */
    protected QueryableRepositoryInterface $repository;

    public function user(): Authenticatable|User
    {
        return Auth::user();
    }

    /**
     * @param TRepository $repository
     *
     * @return TRepository
     */
    public function setRepository(QueryableRepositoryInterface $repository): QueryableRepositoryInterface
    {
        return $this->repository = $repository;
    }

    /**
     * @return TRepository
     */
    public function getRepository(): QueryableRepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Get filtered, sorted, and included resources.
     *
     * @return Collection<int, TModel>
     */
    public function getFiltered(array $columns = ['*']): Collection
    {
        return $this->repository->getFiltered($columns);
    }

    /**
     * Get all resources
     *
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->repository->all($columns);
    }

    /**
     * Get paginated resources
     *
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $columns);
    }

    /**
     * Find resource by id
     *
     * @return TModel|null
     */
    public function find(string|int $id, array $columns = ['*']): ?Model
    {
        return $this->repository->find($id, $columns);
    }

    /**
     * Find resource or fail
     *
     * @return TModel
     */
    public function findOrFail(string|int $id, array $columns = ['*']): Model
    {
        return $this->repository->findOrFail($id, $columns);
    }

    /**
     * Create new resource
     *
     * @param array<string, mixed>|Data $data
     *
     * @return TModel
     */
    public function create(array|Data $data): Model
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }

        return $this->repository->create($data);
    }

    /**
     * Update resource
     *
     * @param array<string, mixed>|Data $data
     */
    public function update(string|int $id, array|Data $data): bool
    {
        if ($data instanceof Data) {
            $data = $data->toArray();
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete resource
     */
    public function delete(string|int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Query resources by where condition
     *
     * @return \Illuminate\Database\Eloquent\Builder<TModel>
     */
    public function query()
    {
        return $this->repository->query();
    }

    /**
     * Get resources by where condition
     *
     * @return Collection<int, TModel>
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): Collection
    {
        return $this->repository->where($column, $operator, $value, $boolean);
    }

    /**
     * Get first resource by where condition
     *
     * @return TModel|null
     */
    public function firstWhere(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): ?Model
    {
        return $this->repository->firstWhere($column, $operator, $value, $boolean);
    }

    /**
     * Check if resource exists
     */
    public function exists(string|int $id): bool
    {
        return $this->repository->exists($id);
    }

    /**
     * Count resources
     */
    public function count(): int
    {
        return $this->repository->count();
    }
}
