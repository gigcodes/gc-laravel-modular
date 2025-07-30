<?php

use Modules\Shared\Exceptions\BaseException;

// Create a concrete implementation for testing
class TestException extends BaseException
{
    protected int $statusCode = 422;

    protected string $errorMessage = 'Test error message';
}

test('base exception can be created with default message', function () {
    $exception = new TestException;

    expect($exception->getMessage())->toBe('Test error message');
    expect($exception->getStatusCode())->toBe(422);
});

test('base exception can be created with custom message', function () {
    $exception = new TestException('Custom error message');

    expect($exception->getMessage())->toBe('Custom error message');
    expect($exception->getStatusCode())->toBe(422);
});

test('base exception can be created with custom code and previous exception', function () {
    $previous  = new \Exception('Previous exception');
    $exception = new TestException('Custom message', 123, $previous);

    expect($exception->getMessage())->toBe('Custom message');
    expect($exception->getCode())->toBe(123);
    expect($exception->getPrevious())->toBe($previous);
});

test('base exception can render json response', function () {
    $exception = new TestException('Test error');

    $response = $exception->render();
    $data     = $response->getData(true);

    expect($response->getStatusCode())->toBe(422);
    expect($data['success'])->toBeFalse();
    expect($data['message'])->toBe('Test error');
    expect($data)->not->toHaveKey('errors');
});

test('base exception can render json response with errors', function () {
    $exception = new TestException('Test error');
    $errors    = ['field1' => ['Error 1'], 'field2' => ['Error 2']];

    $exception->setErrors($errors);
    $response = $exception->render();
    $data     = $response->getData(true);

    expect($response->getStatusCode())->toBe(422);
    expect($data['success'])->toBeFalse();
    expect($data['message'])->toBe('Test error');
    expect($data['errors'])->toBe($errors);
});

test('base exception set errors method returns self', function () {
    $exception = new TestException;
    $errors    = ['field' => ['Error message']];

    $result = $exception->setErrors($errors);

    expect($result)->toBe($exception);
});

test('base exception uses default message when empty string provided', function () {
    $exception = new TestException('');

    expect($exception->getMessage())->toBe('Test error message');
});
