<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Carbon\Carbon;
use App\Models\User;
use App\Models\House;
use App\Models\AdRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdsController extends Controller
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

        // 驗證: 是否有 token，沒有即是訪客
        $token = $request->header('X-User-Token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'MSG_PERMISSION_DENY', 'data' => ''], 403);
        }

        // 驗證: 無效 token
        $user = User::where('token', $token)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'MSG_INVALID_TOKEN', 'data' => ''], 401);
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
