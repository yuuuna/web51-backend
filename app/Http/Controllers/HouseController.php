<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\User;
use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HouseController extends Controller
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
     * 刊登房屋
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
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

        // 驗證: Require
        if (!$request->has('area_id') || !$request->has('title') || !$request->has('price') || !$request->has('license_date')) {
            return response()->json(['success' => false, 'message' => 'MSG_MISSING_FIELD', 'data' => ''], 400);
        }

        // 驗證: 型態
        $validator = Validator::make($request->all(), [
            'area_id' => 'integer',
            'title' => 'string',
            'price' => 'string',
            'license_date' => 'date'
        ]);
        $area = Area::where('id', $request->input('area_id'))->first();
        if ($validator->fails() || !$area) {
            return response()->json(['success' => false, 'message' => 'MSG_WROND_DATA_TYPE', 'data' => ''], 400);
        }

        // 驗證: 圖片格式
        $validator = Validator::make($request->all(), [
            'thumbnail_path' => 'nullable|image',
        ]);
        $path = null;
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'MSG_IMAGE_CAN_NOT_PROCESS', 'data' => ''], 400);
        } else if ($request->file('thumbnail_path')) {
            // 上傳圖片: 使用亂數名稱
            $path = $request->file('thumbnail_path')->store('house');

            // 上傳圖片: 指定檔名
            // $filename = $request->file('thumbnail_path')->getClientOriginalName();
            // $path = $request->file('thumbnail_path')->storeAs('/house', $filename);
        }

        // 新增房屋
        $house = new House;
        $house->user_id = $user->id;
        $house->area_id = $area->id;
        $house->title = $request->input('title');
        $house->price = $request->input('price');
        $house->thumbnail_path = $path;
        $house->total_area = $request->input('total_area', 0);
        $house->public_area = $request->input('public_area', 0);
        $house->bedroom_count = $request->input('bedroom_count', 0);
        $house->living_room_count = $request->input('living_room_count', 0);
        $house->dining_room_count = $request->input('dining_room_count', 0);
        $house->kitchen_count = $request->input('kitchen_count', 0);
        $house->license_date = $request->input('license_date');
        $house->floor = $request->input('floor', 0);
        $house->bathroom_count = $request->input('bathroom_count', 0);
        $house->save();

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
