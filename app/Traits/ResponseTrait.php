<?php

namespace App\Traits;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

trait ResponseTrait
{
    protected function sendResponse($data = [], $status = true, $msg = 'Error', $code = 200)
    {
        return response()->json([
            'status' => $status,
            'message' => $msg,
            'data' => $data,
        ], $code);
    }

    protected function successResponse($data = [], $msg = 'Success', $code = 200, $meta = [])
    {

        return response()->json([
            'status' => true,
            'message' => $msg,
            'data' => $data instanceof Paginator || $data instanceof LengthAwarePaginator
                ? $data->items() : $data,
            'meta' => $this->getMeta($data, $meta),
        ], $code);
    }

    public function getMeta($data = [], $meta = [])
    {
        if ($data instanceof AnonymousResourceCollection) {
            $data = $data->resource;
        }

        if ($data instanceof LengthAwarePaginator || $data instanceof Paginator) {
            $meta = [
                'current_page' => $data->currentPage(),
                'from' => $data->firstItem(),
                'last_page' => $data->lastPage(),
                'path' => request()->url(),
                'per_page' => $data->perPage(),
                'to' => $data->lastItem(),
                'total' => $data->total(),
                ...$meta,
            ];
        }

        return $meta;
    }

    protected function errorResponse($errors = [], $msg = '', $code = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $msg,
            'errors' => $errors,
        ], $code);
    }

    protected function jsonResponse($data = [], $msg = '', $code = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $msg,
        ], $code);
    }
}
