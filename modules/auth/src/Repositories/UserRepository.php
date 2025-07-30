<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\Repositories\Base\Concretes\QueryableRepository;
use Modules\Auth\Interfaces\UserRepositoryInterface;
use Modules\Auth\Models\User;

/**
 * @extends QueryableRepository<User>
 */
class UserRepository extends QueryableRepository implements UserRepositoryInterface
{
    public function getModelClass(): string
    {
        return User::class;
    }

    public function getByCompanyWithEmployeeDetails(int $companyId): Collection
    {
        return $this->model->newQuery()
            ->where('company_id', $companyId)
            ->with('employeeDetail')
            ->get();
    }

    public function updateByModel(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }
}
