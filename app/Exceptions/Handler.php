<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use LaravelJsonApi\Core\Exceptions\JsonApiException as ExceptionsJsonApiException;
use Throwable;
use LaravelJsonApi\Exceptions\JsonApiException;
use LaravelJsonApi\Exceptions\ExceptionParser;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        JsonApiException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(
            ExceptionParser::make()->renderable()
        );
    }
}
