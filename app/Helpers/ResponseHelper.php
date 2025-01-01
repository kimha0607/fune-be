<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function success($data = [], $message = 'Operation successful', $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error($message = 'An error occurred', $errors = [], $code = 400)
    {
        return response()->json([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function customError($message = 'An error occurred', $errors = [], $code = 400)
    {
        $formattedErrors = [];

        foreach ($errors as $field => $errorCodes) {
            foreach ($errorCodes as $errorCode) {
                $formattedErrors[] = [
                    'code' => $errorCode,
                    'field' => $field,
                ];
            }
        }

        return response()->json([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'errors' => $formattedErrors,
        ], $code);
    }
}
