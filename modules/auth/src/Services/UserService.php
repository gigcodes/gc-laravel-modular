<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Interfaces\UserRepositoryInterface;
use Modules\Auth\Interfaces\UserServiceInterface;
use Modules\Auth\Models\User;
use Modules\Shared\Services\Base\Concretes\BaseService;

/**
 * @extends BaseService<User, UserRepositoryInterface>
 *
 * @property UserRepositoryInterface $repository
 */
class UserService extends BaseService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
        $this->setRepository($this->userRepository);
    }
}
