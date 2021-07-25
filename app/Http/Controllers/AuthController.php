<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * 使用者登入
     *
     * @param Request $request
     * @return void
     */
    public function login(Request $request)
    {
        // 驗證欄位: Request
        if (!$request->has('email') || !$request->has('password')) {
            return response()->json(['success' => false, 'message' => 'MSG_MISSING_FIELD', 'data' => ''], 400);
        }

        // 驗證欄位: 型態
        $validator = Validator::make($request->all(), [
            'email' => 'string',
            'password' => 'string'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'MSG_WROND_DATA_TYPE', 'data' => ''], 400);
        }

        $user = User::where('email', $request->input('email'))
            ->first();

        if (Hash::check($request->input('password'), $user->password)) {
            // 登入成功
            $token = hash('sha256', $request->input('email'));
            $user->token = $token;
            $user->save();

            return response()->json(['success' => true, 'message' => '', 'data' => $token]);
        }

        // 登入失敗
        return response()->json(['success' => false, 'message' => 'MSG_INVALID_LOGIN', 'data' => ''], 403);
    }
}
