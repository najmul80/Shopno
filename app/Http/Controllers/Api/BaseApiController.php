<?php

namespace App\Http\Controllers\Api; 

use App\Http\Controllers\Controller; 
use Illuminate\Http\JsonResponse;  

class BaseApiController extends Controller 
{
    /**
     * Send a success JSON response.
     *
     * @param mixed $data The data to be sent in the response.
     * @param string $message A success message.
     * @param int $statusCode HTTP status code (default is 200 OK).
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, string $message = 'Operation successful.', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message An error message.
     * @param int $statusCode HTTP status code (default is 400 Bad Request).
     * @param array $errors Optional array of specific validation errors or other error details.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = []): JsonResponse
    {
        $responsePayload = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $responsePayload['errors'] = $errors;
        }

        return response()->json($responsePayload, $statusCode);
    }

    /**
     * Send a Not Found (404) JSON response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Send a Forbidden (403) JSON response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbiddenResponse(string $message = 'You do not have permission to access this resource.'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Send an Unauthorized (401) JSON response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthenticated or invalid credentials.'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }
}