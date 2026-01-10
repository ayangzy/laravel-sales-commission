<?php

namespace SalesCommission\Pro\Http\Controllers\Api;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class BaseApiController extends Controller
{
    /**
     * Return a success response.
     */
    protected function success($data = null, string $message = null, int $status = 200): JsonResponse
    {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $status);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $status);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $resource = 'Resource'): JsonResponse
    {
        return $this->error("{$resource} not found.", 404);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized access.'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a validation error response.
     */
    protected function validationError(array $errors): JsonResponse
    {
        return $this->error('Validation failed.', 422, $errors);
    }
}
