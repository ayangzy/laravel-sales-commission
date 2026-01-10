<?php

namespace SalesCommission\Pro\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            return response()->json([
                'success' => false,
                'message' => 'The requested resource or route was not found.',
                'error_code' => 'NOT_FOUND',
            ], 404);
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
