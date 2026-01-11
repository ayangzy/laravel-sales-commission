<?php

namespace SalesCommission\Pro\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiExceptionHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ModelNotFoundException $e) {
            $model = class_basename($e->getModel());
            $modelName = $this->humanizeModelName($model);
            
            return response()->json([
                'success' => false,
                'message' => "{$modelName} not found.",
                'error_code' => 'RESOURCE_NOT_FOUND',
            ], 404);
        } catch (NotFoundHttpException $e) {
            // Check if it's a model binding issue
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                $model = class_basename($previous->getModel());
                $modelName = $this->humanizeModelName($model);
                
                return response()->json([
                    'success' => false,
                    'message' => "{$modelName} not found.",
                    'error_code' => 'RESOURCE_NOT_FOUND',
                ], 404);
            }
            
            // Try to extract model info from the message
            $message = $e->getMessage();
            if (preg_match('/No query results for model \[([^\]]+)\]/', $message, $matches)) {
                $fullModel = $matches[1];
                $model = class_basename($fullModel);
                $modelName = $this->humanizeModelName($model);
                
                return response()->json([
                    'success' => false,
                    'message' => "{$modelName} not found.",
                    'error_code' => 'RESOURCE_NOT_FOUND',
                ], 404);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
                'error_code' => 'ROUTE_NOT_FOUND',
            ], 404);
        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please provide a valid API token.',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred.',
                'error_code' => 'HTTP_ERROR',
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Illuminate\Support\Facades\Log::error('Commission API Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Convert model class name to human readable format.
     */
    protected function humanizeModelName(string $model): string
    {
        $names = [
            'CommissionPlan' => 'Commission Plan',
            'CommissionTier' => 'Commission Tier',
            'CommissionRule' => 'Commission Rule',
            'CommissionEarning' => 'Commission Earning',
            'Payout' => 'Payout',
            'CommissionClawback' => 'Clawback',
            'CommissionSplit' => 'Commission Split',
        ];

        return $names[$model] ?? str_replace('_', ' ', \Illuminate\Support\Str::snake($model));
    }
}

