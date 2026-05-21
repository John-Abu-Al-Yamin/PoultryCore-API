<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $extra = []
    ): JsonResponse {
        if (
            $data instanceof AnonymousResourceCollection &&
            $data->resource instanceof \Illuminate\Pagination\AbstractPaginator
        ) {

            // Get the original pagination response
            $originalResponse = $data->response()->getData(true);

            // Merge with your custom structure while preserving pagination
            return response()->json(array_merge([
                'success' => true,
                'status' => $statusCode,
                'message' => $message,
                'data' => $originalResponse['data'],
                'links' => $originalResponse['links'] ?? null,
                'meta' => $originalResponse['meta'] ?? null,
            ], $extra), $statusCode);
        }

        return response()->json(array_merge([
            'success' => true,
            'status' => $statusCode,
            'message' => $message,
            'data' => $data,
        ], $extra), $statusCode);
    }

    public static function emptyData(
        string $message = 'Success',
        int $statusCode = 201
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'status' => $statusCode,
            'message' => $message,
        ], $statusCode);
    }

    public static function error(
        string $message = 'Error',
        int $statusCode = 400,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'status' => $statusCode,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
