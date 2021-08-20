<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Carbon\Carbon;
use App\Models\User;
use App\Models\House;
use App\Models\AdRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdsController extends Controller
{
    /**
     * 瀏覽精選房屋
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // 驗證: 無效 token
        $token = $request->header('X-User-Token');
        $user = User::where('token', $token)->first();
        if (!$token || !$user) {
            return response()->json(['success' => false, 'message' => 'MSG_INVALID_TOKEN', 'data' => ''], 401);
        }

        // 驗證: 是否為管理員
        if ($user->role !== 'ADMIN') {
            return response()->json(['success' => false, 'message' => 'MSG_PERMISSION_DENY', 'data' => ''], 403);
        }

        $ads = DB::table('ads')
            ->join('houses', 'houses.id', '=', 'ads.house_id')
            ->select(
                'houses.title',
                'houses.thumbnail_path',
                'houses.price',
                DB::raw('houses.price / houses.total_area AS unit_price'),
                'houses.total_area',
                DB::raw('houses.bedroom_count + houses.living_room_count + houses.dining_room_count + houses.kitchen_count + houses.bathroom_count AS room_count')
            )
            // 未被刪除的房屋
            ->where('houses.deleted_at', '=', null)
            ->where('houses.user_id', '=', $user->id)
            ->simplePaginate(10);

        return $ads;
    }

    /**
     * 申請為精選房屋
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

        $ad_request = AdRequest::where('house_id', $house->id)->first();
        if ($ad_request) {
            // 驗證: 精選房屋申請中
            if ($ad_request->review_status === 'REVIEWING') {
                return response()->json(['success' => false, 'message' => 'MSG_HOUSE_APPLIED', 'data' => ''], 409);
            } else {
                // 驗證: 房屋已是精選房屋
                $ad = Ad::where('ad_request_id', $ad_request->id)->first();
                $date_now = Carbon::now();
                if ($ad && $ad->publish_end_date > $date_now) {
                    return response()->json(['success' => false, 'message' => 'MSG_HOUSE_ADVERTISED', 'data' => ''], 409);
                }
            }
        }

        // 新增 Ad Request
        $ad_request = new AdRequest();
        $ad_request->house_id = $house->id;
        $ad_request->review_status = 'REVIEWING';
        $ad_request->save();

        // 新增 Ad
        $ad = new Ad();
        $ad->ad_request_id = $ad_request->id;
        $ad->house_id = $house->id;
        $ad->save();

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }

    /**
     * 審核精選房屋
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // 驗證: 無效 token
        $token = $request->header('X-User-Token');
        $user = User::where('token', $token)->first();
        if (!$token || !$user) {
            return response()->json(['success' => false, 'message' => 'MSG_INVALID_TOKEN', 'data' => ''], 401);
        }

        // 驗證: 是否為管理員
        if ($user->role !== 'ADMIN') {
            return response()->json(['success' => false, 'message' => 'MSG_PERMISSION_DENY', 'data' => ''], 403);
        }

        // 驗證: Require
        if (!$request->has('review_status')) {
            return response()->json(['success' => false, 'message' => 'MSG_MISSING_FIELD', 'data' => ''], 400);
        }

        // 驗證: 型態
        $validator = Validator::make($request->all(), [
            'review_status' => 'in:APPROVE,REJECT'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'MSG_WROND_DATA_TYPE', 'data' => ''], 400);
        }

        // 驗證: 不存在的精選
        $ad = Ad::where('id', $id)->first();
        if (!$ad) {
            return response()->json(['success' => false, 'message' => 'MSG_AD_NOT_EXISTS', 'data' => ''], 409);
        }

        // 驗證: 不存在 house
        $house = House::where('id', $ad->house_id)->first();
        if (!$house) {
            return response()->json(['success' => false, 'message' => 'MSG_HOUSE_NOT_EXISTS', 'data' => ''], 404);
        }

        // 更新 Ad Request
        $ad_request = $ad->ad_request;
        $ad_request->review_status = $request->input('review_status');
        $ad_request->reviewer_id = $user->id;
        $ad_request->reviewed_at = Carbon::now();
        $ad_request->save();

        // 更新 Ad
        if ($request->input('review_status') === 'APPROVE') {
            $ad->publish_start_date = Carbon::now();
            $ad->publish_end_date = Carbon::now()->addYear();
            $ad->save();
        }

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }

    /**
     * 下架精選房屋
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        // 驗證: 無效 token
        $token = $request->header('X-User-Token');
        $user = User::where('token', $token)->first();
        if (!$token || !$user) {
            return response()->json(['success' => false, 'message' => 'MSG_INVALID_TOKEN', 'data' => ''], 401);
        }

        // 驗證: 是否為管理員
        if ($user->role !== 'ADMIN') {
            return response()->json(['success' => false, 'message' => 'MSG_PERMISSION_DENY', 'data' => ''], 403);
        }

        // 驗證: 不存在的精選
        $ad = Ad::where('id', $id)->first();
        if (!$ad) {
            return response()->json(['success' => false, 'message' => 'MSG_AD_NOT_EXISTS', 'data' => ''], 409);
        }

        // 驗證: 不存在 house
        $house = House::where('id', $ad->house_id)->first();
        if (!$house) {
            return response()->json(['success' => false, 'message' => 'MSG_HOUSE_NOT_EXISTS', 'data' => ''], 404);
        }

        // 刪除 Ad
        $ad->delete();

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }
}
