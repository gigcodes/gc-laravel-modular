<?php

declare(strict_types=1);

namespace Modules\Auth\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Models\User;
use Modules\Shared\Repositories\Base\Contracts\QueryableRepositoryInterface;

/**
 * @extends QueryableRepositoryInterface<User>
 */
interface UserRepositoryInterface extends QueryableRepositoryInterface
{
    /**
     * Get users by company with employee details
     *
     * @return Collection<int, User>
     */
    public function getByCompanyWithEmployeeDetails(int $companyId): Collection;

    public function updateByModel(User $user, array $data): User;
}
