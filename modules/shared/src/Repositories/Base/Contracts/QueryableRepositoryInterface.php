<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories\Base\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends BaseRepositoryInterface<TModel>
 */
interface QueryableRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get a query builder instance with filters, sorts, and includes applied.
     *
     * @return QueryBuilder<TModel>
     */
    public function query(): QueryBuilder;

    /**
     * Get filtered, sorted, and included resources.
     *
     * @param array<string> $columns
     *
     * @return Collection<int, TModel>
     */
    public function getFiltered(array $columns = ['*']): Collection;

    /**
     * Get paginated, filtered, sorted, and included resources.
     *
     * @param array<string> $columns
     *
     * @return LengthAwarePaginator<TModel>
     */
    public function paginateFiltered(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Get allowed filters for this repository.
     */
    public function getAllowedFilters(): array;

    /**
     * Get allowed sorts for this repository.
     */
    public function getAllowedSorts(): array;

    /**
     * Get allowed includes for this repository.
     */
    public function getAllowedIncludes(): array;

    /**
     * Get allowed fields for this repository.
     */
    public function getAllowedFields(): array;
}
