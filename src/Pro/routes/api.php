<?php

use Illuminate\Support\Facades\Route;
use SalesCommission\Pro\Http\Controllers\Api\CommissionPlanController;
use SalesCommission\Pro\Http\Controllers\Api\CommissionTierController;
use SalesCommission\Pro\Http\Controllers\Api\CommissionRuleController;
use SalesCommission\Pro\Http\Controllers\Api\CommissionEarningController;
use SalesCommission\Pro\Http\Controllers\Api\PayoutController;
use SalesCommission\Pro\Http\Controllers\Api\ClawbackController;
use SalesCommission\Pro\Http\Controllers\Api\AgentController;
use SalesCommission\Pro\Http\Controllers\Api\CommissionSplitController;
use SalesCommission\Pro\Http\Controllers\Api\CalculateController;
use SalesCommission\Pro\Http\Controllers\Api\StatsController;
use SalesCommission\Pro\Http\Middleware\ApiExceptionHandler;

/*
|--------------------------------------------------------------------------
| Sales Commission API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the SalesCommissionServiceProvider when a
| valid Pro license is active.
|
*/

Route::prefix('api/commissions')
    ->middleware(['api', 'auth:sanctum', ApiExceptionHandler::class])
    ->group(function () {
        
        // Commission Plans
        Route::apiResource('plans', CommissionPlanController::class);
        Route::post('plans/{plan}/activate', [CommissionPlanController::class, 'activate']);
        Route::post('plans/{plan}/deactivate', [CommissionPlanController::class, 'deactivate']);
        Route::post('plans/{plan}/set-default', [CommissionPlanController::class, 'setDefault']);
        
        // Commission Tiers (nested under plans)
        Route::apiResource('plans.tiers', CommissionTierController::class)->shallow();
        
        // Commission Rules (nested under plans)
        Route::apiResource('plans.rules', CommissionRuleController::class)->shallow();
        
        // Commission Earnings
        Route::get('earnings', [CommissionEarningController::class, 'index']);
        Route::get('earnings/{earning}', [CommissionEarningController::class, 'show']);
        Route::post('earnings/{earning}/mark-payable', [CommissionEarningController::class, 'markPayable']);
        Route::get('earnings/by-agent/{agent}', [CommissionEarningController::class, 'byAgent']);
        Route::get('earnings/by-period/{period}', [CommissionEarningController::class, 'byPeriod']);
        
        // Payouts - specific routes MUST come before resource routes
        Route::get('payouts/pending', [PayoutController::class, 'pending']);
        Route::post('payouts/generate', [PayoutController::class, 'generate']);
        Route::apiResource('payouts', PayoutController::class)->only(['index', 'show', 'store']);
        Route::post('payouts/{payout}/approve', [PayoutController::class, 'approve']);
        Route::post('payouts/{payout}/reject', [PayoutController::class, 'reject']);
        Route::post('payouts/{payout}/process', [PayoutController::class, 'process']);
        
        // Clawbacks
        Route::get('clawbacks', [ClawbackController::class, 'index']);
        Route::get('clawbacks/{clawback}', [ClawbackController::class, 'show']);
        Route::post('clawbacks', [ClawbackController::class, 'store']);
        Route::post('clawbacks/partial', [ClawbackController::class, 'storePartial']);
        Route::post('clawbacks/for-commissionable', [ClawbackController::class, 'forCommissionable']);
        
        // Agent Earnings
        Route::get('agents/{agent}/earnings', [AgentController::class, 'earnings']);
        Route::get('agents/{agent}/total', [AgentController::class, 'total']);
        Route::get('agents/{agent}/pending', [AgentController::class, 'pending']);
        Route::get('agents/{agent}/tier', [AgentController::class, 'currentTier']);
        Route::get('agents/{agent}/payouts', [AgentController::class, 'payouts']);
        
        // Commission Splits
        Route::get('splits', [CommissionSplitController::class, 'index']);
        Route::get('splits/{split}', [CommissionSplitController::class, 'show']);
        Route::post('splits/calculate', [CommissionSplitController::class, 'calculate']);
        
        // Calculate Commission (on-demand)
        Route::post('calculate', [CalculateController::class, 'calculate']);
        Route::post('calculate/batch', [CalculateController::class, 'calculateBatch']);
        Route::post('calculate/preview', [CalculateController::class, 'preview']);
        
        // Stats & Reports
        Route::get('stats/overview', [StatsController::class, 'overview']);
        Route::get('stats/earnings', [StatsController::class, 'earnings']);
        Route::get('stats/payouts', [StatsController::class, 'payouts']);
        Route::get('stats/top-earners', [StatsController::class, 'topEarners']);
        Route::get('stats/by-plan', [StatsController::class, 'byPlan']);
        Route::get('stats/trends', [StatsController::class, 'trends']);
    });

// API Documentation (no auth required)
Route::prefix('api/commissions')
    ->middleware(['api'])
    ->group(function () {
        Route::get('docs', [\SalesCommission\Pro\Http\Controllers\Api\DocsController::class, 'index']);
        Route::get('docs/openapi.yaml', [\SalesCommission\Pro\Http\Controllers\Api\DocsController::class, 'spec']);
    });
