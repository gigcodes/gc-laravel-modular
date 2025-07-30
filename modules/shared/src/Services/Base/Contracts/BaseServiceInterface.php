<?php

declare(strict_types=1);

namespace Modules\Shared\Services\Base\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;

/**
 * @template TModel of Model
 */
interface BaseServiceInterface
{
    /**
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * @return Collection<int, TModel>
     */
    public function getFiltered(array $columns = ['*']): Collection;

    /**
     * @return TModel|null
     */
    public function find(string|int $id, array $columns = ['*']): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(string|int $id, array $columns = ['*']): Model;

    /**
     * @param array<string, mixed>|Data $data
     *
     * @return TModel
     */
    public function create(array|Data $data): Model;

    /**
     * @param array<string, mixed>|Data $data
     */
    public function update(string|int $id, array|Data $data): bool;

    public function delete(string|int $id): bool;
}
