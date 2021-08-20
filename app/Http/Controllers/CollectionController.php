<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\House;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CollectionController extends Controller
{
    /**
     * 瀏覽收藏列表
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
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

        $collection = DB::table('collections')
            ->join('houses', 'houses.id', '=', 'collections.house_id')
            ->select(
                'houses.id',
                'houses.title',
                'houses.thumbnail_path',
                'houses.price',
                DB::raw('houses.price / houses.total_area AS unit_price'),
                'houses.total_area',
                DB::raw('houses.bedroom_count + houses.living_room_count + houses.dining_room_count + houses.kitchen_count + houses.bathroom_count AS room_count'),
                'collections.created_at'
            )
            // 未被刪除的房屋
            ->where('houses.deleted_at', '=', null)
            ->where('houses.user_id', '=', $user->id)
            ->orderBy('created_at', 'desc')
            ->simplePaginate(10);

        return $collection;
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
     * 將房屋從收藏列表移除
     *
     * @param Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        // 驗證: 不存在 house
        $house = House::where('id', $id)->first();
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

        // 驗證: 房屋不存在收藏列表中
        $collection = Collection::where('user_id', $user->id)
                                ->where('house_id', $house->id)
                                ->first();
        if (!$collection) {
            return response()->json(['success' => false, 'message' => 'MSG_COLLECTION_NOT_EXISTS', 'data' => ''], 404);
        }

        // 移除 Collection
        $collection->delete();

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }
}
