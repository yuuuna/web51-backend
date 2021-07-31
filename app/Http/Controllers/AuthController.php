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
        // 驗證: Require
        if (!$request->has('email') || !$request->has('password')) {
            return response()->json(['success' => false, 'message' => 'MSG_MISSING_FIELD', 'data' => ''], 400);
        }

        // 驗證: 型態
        $validator = Validator::make($request->all(), [
            'email' => 'string',
            'password' => 'string'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'MSG_WROND_DATA_TYPE', 'data' => ''], 400);
        }

        $user = User::where('email', $request->input('email'))->first();

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

    /**
     * 使用者登出
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request)
    {
        $token = $request->header('X-User-Token');
        $user = User::where('token', $token)->first();
        if ($user) {
            // 有效 token
            $user->token = null;
            $user->save();
            return response()->json(['success' => true, 'message' => '', 'data' => '']);
        }

        // 無效 token
        return response()->json(['success' => false, 'message' => 'MSG_INVALID_TOKEN', 'data' => ''], 401);
    }

    /**
     * 使用者註冊
     *
     * @param Request $request
     * @return void
     */
    public function register(Request $request)
    {
        // 驗證: Require
        if (!$request->has('email') || !$request->has('password') || !$request->has('nickname')) {
            return response()->json(['success' => false, 'message' => 'MSG_MISSING_FIELD', 'data' => ''], 400);
        }

        // 驗證: 型態
        $validator = Validator::make($request->all(), [
            'email' => 'string',
            'password' => 'string',
            'nickname' => 'string'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'MSG_WROND_DATA_TYPE', 'data' => ''], 400);
        }

        // 驗證: 使用者是否已存在
        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            return response()->json(['success' => false, 'message' => 'MSG_USER_EXISTS', 'data' => ''], 409);
        }

        // 新增使用者進資料庫 (方法一)
        $user = User::create([
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'nickname' => $request->input('nickname')
        ]);

        // 新增使用者進資料庫 (方法二)
        // $user = new User;
        // $user->email = $request->input('email');
        // $user->password = Hash::make($request->input('password'));
        // $user->nickname = $request->input('nickname');
        // $user->save();

        return response()->json(['success' => true, 'message' => '', 'data' => '註冊成功']);
    }
}
