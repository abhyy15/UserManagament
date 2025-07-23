<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    public function sendResponse($result, $message = '', $code = 200)
    {
        return response()->json([
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ], $code);
    }

    public function sendError($error, $code = 400, $data = [])
    {
        return response()->json([
            'success' => false,
            'message' => $error,
            'data'    => $data
        ], $code);
    }

    public function sendValidationError($validator)
    {
        return $this->sendError('Validation Error', 422, $validator->errors());
    }
}
