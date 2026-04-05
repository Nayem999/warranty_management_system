<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    public function success(mixed $data = null, string $message = 'Operation completed successfully.', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data instanceof LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['pagination'] = [
                'current_page' => $data->currentPage(),
                'limit' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ];
        } elseif ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    public function error(string $message = 'An error occurred.', int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    public function unauthorized(string $message = 'Unauthenticated. Please login.'): JsonResponse
    {
        return $this->error($message, 401);
    }

    public function forbidden(string $message = 'You do not have permission to perform this action.'): JsonResponse
    {
        return $this->error($message, 403);
    }

    public function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error($message, 404);
    }

    public function validationError(mixed $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    public function created(mixed $data = null, string $message = 'Resource created successfully.'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    public function updated(mixed $data = null, string $message = 'Resource updated successfully.'): JsonResponse
    {
        return $this->success($data, $message);
    }

    public function deleted(string $message = 'Resource deleted successfully.'): JsonResponse
    {
        return $this->success(null, $message);
    }

    public function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
