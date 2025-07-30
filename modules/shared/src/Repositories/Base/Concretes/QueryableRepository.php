<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories\Base\Concretes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Shared\Repositories\Base\Contracts\QueryableRepositoryInterface;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends BaseRepository<TModel>
 *
 * @implements QueryableRepositoryInterface<TModel>
 */
abstract class QueryableRepository extends BaseRepository implements QueryableRepositoryInterface
{
    /**
     * Get a query builder instance with filters, sorts, and includes applied.
     *
     * @return QueryBuilder<TModel>
     */
    public function query(): QueryBuilder
    {
        /** @var QueryBuilder<TModel> $query */
        $query = QueryBuilder::for($this->getModelClass())
            ->allowedFilters($this->getAllowedFilters())
            ->allowedSorts($this->getAllowedSorts())
            ->allowedFields($this->getAllowedFields())
            ->allowedIncludes($this->getAllowedIncludes());

        return $query;
    }

    /**
     * Get filtered, sorted, and included resources.
     *
     * @return Collection<int, TModel>
     */
    public function getFiltered(array $columns = ['*']): Collection
    {
        /** @var Collection<int, TModel> $result */
        $result = $this->query()->get($columns);

        return $result;
    }

    /**
     * Get paginated, filtered, sorted, and included resources.
     *
     * @return LengthAwarePaginator<TModel>
     */
    public function paginateFiltered(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $perPage = request()->input('per_page', $perPage) ?? $perPage;

        /** @var LengthAwarePaginator<TModel> $result */
        $result = $this->query()->paginate($perPage, $columns);

        return $result;
    }

    /**
     * Get allowed filters for this repository.
     */
    public function getAllowedFilters(): array
    {
        return [];
    }

    /**
     * Get allowed sorts for this repository.
     */
    public function getAllowedSorts(): array
    {
        return [];
    }

    /**
     * Get allowed includes for this repository.
     */
    public function getAllowedIncludes(): array
    {
        return [];
    }

    /**
     * Get allowed fields for this repository.
     */
    public function getAllowedFields(): array
    {
        return [];
    }
}
