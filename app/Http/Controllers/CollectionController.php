<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\User;
use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * 將房屋加入收藏列表
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // 驗證: Require
        if (!$request->has('house_id')) {
            return response()->json(['success' => false, 'message' => 'MSG_MISSING_FIELD', 'data' => ''], 400);
        }

        // 驗證: 不存在 house
        $house = House::where('id', $request->input('house_id'))->first();
        if (!$house) {
            return response()->json(['success' => false, 'message' => 'MSG_HOUSE_NOT_EXISTS', 'data' => ''], 404);
        }

        // 驗證: 無效 token
        $token = $request->header('X-User-Token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'MSG_INVALID_TOKEN', 'data' => ''], 401);
        }

        // 驗證: 權限不足
        $user = User::where('token', $token)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'MSG_PERMISSION_DENY', 'data' => ''], 403);
        }

        // 驗證: 型態
        $validator = Validator::make($request->all(), [
            'house_id' => 'integer'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'MSG_WROND_DATA_TYPE', 'data' => ''], 400);
        }

        // 驗證: 房屋已在收藏列表中
        $collection = Collection::where('user_id', $user->id)
                                ->where('house_id', $house->id)
                                ->first();
        if ($collection) {
            return response()->json(['success' => false, 'message' => 'MSG_HOUSE_COLLECTED', 'data' => ''], 409);
        }

        // 新增 Collection
        $collection = new Collection;
        $collection->user_id = $user->id;
        $collection->house_id = $house->id;
        $collection->save();

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
