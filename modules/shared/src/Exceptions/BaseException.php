<?php

declare(strict_types=1);

namespace Modules\Shared\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

abstract class BaseException extends Exception
{
    protected int $statusCode = 500;

    protected string $errorMessage = 'An error occurred';

    protected ?array $errors = null;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = $message ?: $this->errorMessage;
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }
}
