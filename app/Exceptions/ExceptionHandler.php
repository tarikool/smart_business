<?php

namespace App\Exceptions;

use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionHandler extends Exception
{
    use ResponseTrait;

    /**
     * Render the exception as a JSON response.
     *
     * @param  Request  $request
     */
    public function render(\Throwable $e, $request): JsonResponse
    {
        if ($e instanceof ValidationException) {
            $firstError = Arr::first(Arr::flatten($e->errors()));

            return $this->errorResponse(
                errors: $e->errors(),
                msg: $firstError ?: $e->getMessage(),
                code: 422
            );
        }

        if ($e instanceof AuthenticationException) {
            $this->errorResponse(
                msg: 'Unauthenticated',
                code: 401
            );
        }

        if ($e instanceof ModelNotFoundException || $e->getPrevious() instanceof ModelNotFoundException) {

            $class = $e->getPrevious()->getModel();
            $model = Str::of($class)->classBasename()->snake(' ');
            $id = implode(',', (array) $e->getPrevious()->getIds());

            return $this->errorResponse(
                msg: $id
                    ? "No $model found for id $id"
                    : "No $model found",
                code: 404
            );
        }

        if ($e instanceof MultipleRecordsFoundException) {
            return $this->errorResponse(
                msg: 'Duplicate information found. Please contact support',
                code: 500
            );
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->errorResponse(
                msg: 'Not found',
                code: 404
            );
        }

        return $this->errorResponse(
            msg: $e->getMessage(),
            code: $this->getStatusCode($e)
        );

    }

    /**
     * Get the HTTP status code for the exception.
     */
    protected function getStatusCode(\Throwable $e): int
    {
        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof ModelNotFoundException) {
            return 404;
        }

        $statusCode = method_exists($e, 'getCode') ? (int) $e->getCode() : 500;

        return $statusCode > 299 && $statusCode < 600
                ? $statusCode : 400;
    }
}
