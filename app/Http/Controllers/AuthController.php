<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success(
            data: [
                'user' => $user,
                'token' => $token,
            ],
            message: 'تم إنشاء الحساب بنجاح'
        );
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('phone', $data['phone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {

            return ApiResponse::error(
                message: 'بيانات الدخول غير صحيحة',
                statusCode: 401,
                errors: [
                    [
                        'field' => 'password',
                        'message' => 'بيانات الدخول غير صحيحة',
                    ],
                ]
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success(
            data: [
                'user' => $user,
                'token' => $token,
            ],
            message: 'تم تسجيل الدخول بنجاح'
        );

    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::emptyData(
            message: 'تم تسجيل الخروج بنجاح',
            statusCode: 200
        );
    }

    public function user(Request $request)
    {
        return ApiResponse::success(
            data: $request->user(),
            message: 'بيانات المستخدم'
        );
    }
}
