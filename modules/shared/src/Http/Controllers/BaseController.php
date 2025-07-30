<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseController
{
    use AuthorizesRequests;
}
