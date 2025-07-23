<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Jobs\BulkUserCreateJob;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required',
            'role_id'  => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $input = $request->all();

        $user = User::create($input);
        $token = $user->createToken('UserToken')->accessToken;

        return $this->sendResponse(['token' => $token, 'user' => $user], 'User registered successfully');
    }

    public function login(Request $request)
    {
        // dd($request->all());
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return $this->sendError('Email Or Password Not Match', 401);
        }

        $user = Auth::user();
        $tokenResult = $user->createToken('UserToken')->accessToken;
        // $token = $tokenResult->plainTextToken;
        
        return $this->sendResponse(['token' => $tokenResult, 'user' => $user], 'User logged in successfully');
    }

    public function logout(Request $request)
    {

         $user = $request->user();
            // dd($user);
        if ($user && $user->token()) {
            $user->token()->revoke();

            return $this->sendResponse([], 'User logged out successfully');
        }
        return $this->sendError('User not authenticated', 401);
    }

    public function registerBulk(Request $request)
    {
        $users = $request->input('users');

        if (!is_array($users)) {
            return $this->sendError('Users should be an array of user data', 422);
        }

        $errors = [];

        foreach ($users as $index => $userData) {
            $validator = Validator::make($userData, [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role_id'  => 'required|exists:roles,id',
            ]);

            if ($validator->fails()) {
                $errors[$index] = $validator->errors();
                continue;
            }

            // Dispatch job for valid user
            BulkUserCreateJob::dispatch($userData);
        }

        if (!empty($errors)) {
            return $this->sendResponse([
                'message' => 'Some users were not valid.',
                'errors'  => $errors
            ], 'Partial success', 207); 
        }

        return $this->sendResponse([], 'All users queued for creation');
    }

}
