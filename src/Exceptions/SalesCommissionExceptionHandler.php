<?php

namespace SalesCommission\Exceptions;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class SalesCommissionExceptionHandler
{
    /**
     * Model name mappings for human-readable messages.
     */
    protected static array $modelNames = [
        'CommissionPlan' => 'Commission Plan',
        'CommissionTier' => 'Commission Tier',
        'CommissionRule' => 'Commission Rule',
        'CommissionEarning' => 'Commission Earning',
        'Payout' => 'Payout',
        'CommissionClawback' => 'Clawback',
        'CommissionSplit' => 'Commission Split',
    ];

    /**
     * Register exception handlers with Laravel's exception handler.
     * Call this in your service provider's boot() method.
     */
    public static function register(): void
    {
        /** @var \Illuminate\Foundation\Exceptions\Handler $exceptions */
        $exceptions = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        // Handle ModelNotFoundException
        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) {
            if (self::isCommissionApiRequest($request)) {
                return self::handleModelNotFound($e);
            }
        });

        // Handle NotFoundHttpException (includes route model binding failures)
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if (self::isCommissionApiRequest($request)) {
                return self::handleNotFoundHttp($e);
            }
        });

        // Handle ValidationException
        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if (self::isCommissionApiRequest($request)) {
                return self::handleValidation($e);
            }
        });

        // Handle AuthenticationException
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if (self::isCommissionApiRequest($request)) {
                return self::handleAuthentication($e);
            }
        });

        // Handle AuthorizationException
        $exceptions->renderable(function (AuthorizationException $e, Request $request) {
            if (self::isCommissionApiRequest($request)) {
                return self::handleAuthorization($e);
            }
        });

        // Handle generic HttpException
        $exceptions->renderable(function (HttpException $e, Request $request) {
            if (self::isCommissionApiRequest($request)) {
                return self::handleHttpException($e);
            }
        });

        // Handle all other exceptions for commission API routes
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if (self::isCommissionApiRequest($request) && !($e instanceof HttpException)) {
                return self::handleGenericException($e);
            }
        });
    }

    /**
     * Check if the request is for the commission API.
     */
    protected static function isCommissionApiRequest(Request $request): bool
    {
        return $request->is('api/commissions/*') || $request->is('api/commissions');
    }

    /**
     * Handle ModelNotFoundException.
     */
    protected static function handleModelNotFound(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());
        $modelName = self::humanizeModelName($model);
        $ids = $e->getIds();

        return response()->json([
            'success' => false,
            'error' => [
                'code' => strtoupper(Str::snake($model)) . '_NOT_FOUND',
                'message' => "{$modelName} not found.",
                'resource_type' => $model,
                'resource_id' => count($ids) === 1 ? $ids[0] : ($ids ?: null),
            ],
        ], 404);
    }

    /**
     * Handle NotFoundHttpException.
     */
    protected static function handleNotFoundHttp(NotFoundHttpException $e): JsonResponse
    {
        // Check if it's a model binding issue
        $previous = $e->getPrevious();
        if ($previous instanceof ModelNotFoundException) {
            return self::handleModelNotFound($previous);
        }

        // Try to extract model info from the message
        $message = $e->getMessage();
        if (preg_match('/No query results for model \[([^\]]+)\]\s*(.*)/', $message, $matches)) {
            $fullModel = $matches[1];
            $model = class_basename($fullModel);
            $modelName = self::humanizeModelName($model);
            $resourceId = trim($matches[2]) ?: null;

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => strtoupper(Str::snake($model)) . '_NOT_FOUND',
                    'message' => "{$modelName} not found.",
                    'resource_type' => $model,
                    'resource_id' => $resourceId,
                ],
            ], 404);
        }

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'ROUTE_NOT_FOUND',
                'message' => 'The requested endpoint was not found.',
            ],
        ], 404);
    }

    /**
     * Handle ValidationException.
     */
    protected static function handleValidation(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'fields' => $e->errors(),
            ],
        ], 422);
    }

    /**
     * Handle AuthenticationException.
     */
    protected static function handleAuthentication(AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required. Please provide a valid API token.',
            ],
        ], 401);
    }

    /**
     * Handle AuthorizationException.
     */
    protected static function handleAuthorization(AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => $e->getMessage() ?: 'You do not have permission to perform this action.',
            ],
        ], 403);
    }

    /**
     * Handle generic HttpException.
     */
    protected static function handleHttpException(HttpException $e): JsonResponse
    {
        $statusCode = $e->getStatusCode();
        $codes = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            409 => 'CONFLICT',
            422 => 'UNPROCESSABLE_ENTITY',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'INTERNAL_ERROR',
            502 => 'BAD_GATEWAY',
            503 => 'SERVICE_UNAVAILABLE',
        ];

        return response()->json([
            'success' => false,
            'error' => [
                'code' => $codes[$statusCode] ?? 'HTTP_ERROR',
                'message' => $e->getMessage() ?: 'An error occurred.',
            ],
        ], $statusCode);
    }

    /**
     * Handle generic exceptions.
     */
    protected static function handleGenericException(Throwable $e): JsonResponse
    {
        // Log the error for debugging
        \Illuminate\Support\Facades\Log::error('Commission API Error', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        // In production, hide details; in debug mode, show them
        if (config('app.debug')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'An unexpected error occurred. Please try again later.',
            ],
        ], 500);
    }

    /**
     * Convert model class name to human readable format.
     */
    protected static function humanizeModelName(string $model): string
    {
        return self::$modelNames[$model] 
            ?? str_replace('_', ' ', Str::headline(Str::snake($model)));
    }
}
