<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\User;

class AuthController extends Controller {

    public function login(Request $request) {

        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = ['user_name' => $request->user_name, 'password' => $request->password, 'is_active' => 1];

        if (! $token = auth()->attempt($credentials)) {
            return response()->json([
                'status' => 401,
                'errors' => "Check your username or password",
            ], 401);
        }

        $user = Auth::user();
        $userRole = $user->role()->first();

        if ($userRole) {
            $this->scope = $userRole->role;
        }

        $token = $user->createToken($user->user_name . '-' . now(), [$userRole->role]);


        return response()->json([
            'status' => 200,
            'message' => 'Authorized.',
            'access_token' => $token->accessToken,
            'user' => array(
                'user_name' => $user->user_name,
                'user_type' => $userRole->role,
            )
        ]);

    }

    public function signUp(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_name' => 'required|string|max:255|unique:users',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'password' => 'required|string|min:6|confirmed',
                'role' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'password' => bcrypt($request->password),
                'user_name' => $request->user_name,
                'remember_token' => Str::random(10),
            ]);

            // add a role to registered user
            Role::create([
                'user_id' => $user->id,
                'role' => $request->role,

            ]);

//            return new UserResource($user);
            return response()->json([
                'status' => 200,
                'message' => "Registered successfully",
            ], 200);

        } catch (\Exception $error) {
            return response()->json([
                "errors" => $error,
                "status" => 403
            ]);
        }
    }

}
