<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Redis;

class UserController extends BaseController
{
    /**
     * Get users with role-based visibility.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            return response()->json(['error' => 'Unauthorized or role missing'], 403);
        }

        if ($user->role->name === 'SuperAdmin') {

            // Redis::del('employee_list');

            if (Redis::exists('employee_list')) {
                $cached = Redis::get('employee_list');
                $getDataFromRedis = json_decode($cached, true);
                return $this->sendResponse($getDataFromRedis);
            }
        
        
            $users = User::withTrashed()->with('role')->get();
            Redis::set('employee_list', json_encode($users));
        
            return $this->sendResponse($users);


            // return $this->sendResponse(User::withTrashed()->with('role')->get());
        }
    
        if ($user->role->name === 'Admin') {
        //    $users = User::with('role')->where('role_id', '!=', 1)->where('id', '!=', $user->id)->get();
            $users = User::with('role')->where('role_id', 3)->withoutTrashed()->get();
            return $this->sendResponse($users);
        }        
    
        return $this->sendResponse(User::with('role')->where('id', $user->id)->get());
    }

    
    public function update(Request $request, $id)
    {
        $authUser = auth()->user();
        $userToUpdate = User::find($id);
        // dd($authUser->id , $userToUpdate->id);
        // $authUser->id === $userToUpdate->id 

        if (!$userToUpdate) {
            return $this->sendError('User not found', 403);
        }

        //if user want to update
        if ($authUser->role->name === 'User') {
            if ($authUser->id !== $userToUpdate->id) {
                return $this->sendError('Unauthorized to update this user', 403);
            }
        }

         // If Admin
        if ($authUser->role->name === 'Admin') {
            if (in_array($userToUpdate->role->name, ['Admin', 'SuperAdmin'])) {
                return $this->sendError('Permission Denied', 403);
            }
        }


        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes',
            'role_id'  => 'sometimes|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator);
        }

        $input = $request->only(['name', 'email', 'password', 'role_id']);
        $userToUpdate->update($input);

        Redis::del('employee_list');

        return $this->sendResponse($userToUpdate, 'User updated successfully');

    }

    public function destroy($id)
    {
        $authUser = auth()->user();
        $user = User::withTrashed()->find($id);
        // dd($user);
        if (!$user) {
            return $this->sendError('User not found', 403);
        }

        if ($authUser->role->name === 'User') {
            return $this->sendError('You are not authorized to delete users', 403);
        }

        if ($authUser->role->name === 'SuperAdmin') {
            $user->delete();
            return $this->sendResponse([], 'User soft-deleted successfully');
        }

        if ($authUser->role->name === 'Admin') {
            if (in_array($user->role->name, ['Admin', 'SuperAdmin'])) {
                return $this->sendError('Permission denied to delete this user', 403);
            }
    
            $user->delete();
            Redis::del('employee_list');
            return $this->sendResponse([], 'User soft-deleted successfully');
        } 

        return $this->sendError('Unauthorized access', 403);

    }


    public function restore($id)
    {
        $authUser = auth()->user();

        $user = User::withTrashed()->find($id);

        if (!$user || !$user->trashed()) {
            return $this->sendError('User not found or not deleted', 404);
        }

        if ($authUser->role->name === 'User') {
            return $this->sendError('You are not authorized to restore users', 403);
        }

        if ($authUser->role->name === 'SuperAdmin') {
            $user->restore();
            return $this->sendResponse($user, 'User restored successfully');
        }

        if ($authUser->role->name === 'Admin') {
            if (in_array($user->role->name, ['Admin', 'SuperAdmin'])) {
                return $this->sendError('Permission denied to restore this user', 403);
            }

            $user->restore();
            return $this->sendResponse($user, 'User restored successfully');
        }
        return $this->sendError('Unauthorized access', 403);
    }


    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->forceDelete();
        return $this->sendResponse([], 'User permanently deleted');
    }
}
