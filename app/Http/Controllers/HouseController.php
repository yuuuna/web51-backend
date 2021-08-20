<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\City;
use App\Models\User;
use App\Models\House;
use App\Models\HousesExtra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HouseController extends Controller
{
    /**
     * 瀏覽房屋
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // 找出精選房屋清單
        $ads = DB::table('ads')
                ->select('house_id', 'id')
                ->where('ads.deleted_at', '=', null)
                ->whereRaw('now() >= ads.publish_start_date')
                ->whereRaw('now() <= ads.publish_end_date');

        $order_field = $request->input('sort', 'created_at');
        $order_method = $request->input('order', 'desc');
        $houses = DB::table('houses')
            ->join('houses_extra', 'houses_extra.house_id', '=', 'houses.id')
            // 關聯精選房屋與房屋
            ->leftJoinSub($ads, 'ads', function($join) {
                $join->on('ads.house_id', '=', 'houses.id');
            })
            ->select(
                // 若精選房屋有資料，顯示精選房屋的
                // DB::raw('IF(ads.house_id IS NULL, 0, ads.id) AS ads_id'),
                // 'ads.house_id',
                'houses.title',
                'houses.thumbnail_path',
                'houses.price',
                DB::raw('houses.price / houses.total_area AS unit_price'),
                'houses.total_area',
                DB::raw('houses.bedroom_count + houses.living_room_count + houses.dining_room_count + houses.kitchen_count + houses.bathroom_count AS room_count')
            )
            // 未被刪除的房屋
            ->where('houses.deleted_at', '=', null)
            ->where(function ($query) use ($request) {
                // 價格最低比對
                $price_low = $request->input('price_low', null);
                if ($price_low) {
                    $query->where('houses.price', '>=', $price_low);
                }

                // 價格最高比對
                $price_high = $request->input('price_high', null);
                if ($price_high) {
                    $query->where('houses.price', '<=', $price_high);
                }

                // 房數比對
                $room_count = $request->input('room_count', null);
                if ($room_count) {
                    $query->whereRaw('houses.bedroom_count + houses.living_room_count + houses.dining_room_count + houses.kitchen_count + houses.bathroom_count = ?', $room_count);
                }

                // 屋齡比對
                $house_age = $request->input('house_age', null);
                if ($house_age) {
                    $query->whereRaw("YEAR(now()) - YEAR(houses.license_date) - (DATE_FORMAT(now(), '%m%d') < DATE_FORMAT(houses.license_date, '%m%d')) = ?", $house_age);
                }
            })
            // ->orderBy('ads_id', 'desc')
            ->orderByRaw('IF(ads.house_id IS NULL, 0, ads.id) desc')
            ->orderBy($order_field, $order_method)
            ->simplePaginate(10);

        return $houses;
    }

    /**
     * 刊登房屋
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
        $city = $area->city;
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

        // 新增房屋延伸資訊
        $houseExtra = new HousesExtra();
        $houseExtra->house_id = $house->id;
        $houseExtra->description = $request->input('description', '');
        $houseExtra->full_address = $city->name . $area->name . $request->input('address', '');
        $houseExtra->save();

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }

    /**
     * 查看房屋
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        // 驗證: 不存在 house
        $house = House::where('id', $id)->first();
        if (!$house) {
            return response()->json(['success' => false, 'message' => 'MSG_HOUSE_NOT_EXISTS', 'data' => ''], 404);
        }

        // 整理房屋資訊
        $house = DB::table('houses')
            ->join('houses_extra', 'houses_extra.house_id', '=', 'houses.id')
            ->select(
                'houses.title',
                'houses.thumbnail_path',
                'houses_extra.description',
                'houses.price',
                DB::raw('houses.price / houses.total_area AS unit_price'),
                'houses.total_area',
                DB::raw("houses.bedroom_count + houses.living_room_count + houses.dining_room_count + houses.kitchen_count + houses.bathroom_count AS room_count"),
                'houses.floor',
                DB::raw("YEAR(now()) - YEAR(houses.license_date) - (DATE_FORMAT(now(), '%m%d') < DATE_FORMAT(houses.license_date, '%m%d')) AS house_age"),
                'houses_extra.full_address'
            )
            ->where('id', '=', $id)
            ->get();

        return response()->json(['success' => true, 'message' => '', 'data' => $house]);
    }

    /**
     * 編輯自己刊登的房屋
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
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
        if (!$user || $user->id !== $house->user_id) {
            return response()->json(['success' => false, 'message' => 'MSG_PERMISSION_DENY', 'data' => ''], 403);
        }

        // 驗證: Require
        if (
            !$request->has('area_id') || !$request->has('title')
            || !$request->has('price') || !$request->has('license_date')
            || !$request->has('total_area') || !$request->has('public_area')
            || !$request->has('bedroom_count') || !$request->has('living_room_count')
            || !$request->has('dining_room_count') || !$request->has('kitchen_count')
            || !$request->has('floor') || !$request->has('bathroom_count')
        ) {
            return response()->json(['success' => false, 'message' => 'MSG_MISSING_FIELD', 'data' => ''], 400);
        }

        // 驗證: 型態
        $validator = Validator::make($request->all(), [
            'area_id' => 'integer',
            'title' => 'string',
            'price' => 'string',
            'license_date' => 'date',
            'total_area' => 'integer',
            'public_area' => 'integer',
            'bedroom_count' => 'integer',
            'living_room_count' => 'integer',
            'dining_room_count' => 'integer',
            'kitchen_count' => 'integer',
            'floor' => 'integer',
            'bathroom_count' => 'integer'
        ]);
        $area = Area::where('id', $request->input('area_id'))->first();
        $city = City::where('id', $request->input('city_id'))->first();
        if ($validator->fails() || !$area || !$city) {
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

            $house->thumbnail_path = $path;
        }

        // 更新房屋
        $house->area_id = $area->id;
        $house->title = $request->input('title');
        $house->price = $request->input('price');
        $house->total_area = $request->input('total_area');
        $house->public_area = $request->input('public_area');
        $house->bedroom_count = $request->input('bedroom_count');
        $house->living_room_count = $request->input('living_room_count');
        $house->dining_room_count = $request->input('dining_room_count');
        $house->kitchen_count = $request->input('kitchen_count');
        $house->license_date = $request->input('license_date');
        $house->floor = $request->input('floor');
        $house->bathroom_count = $request->input('bathroom_count');
        $house->save();

        // 新增房屋延伸資訊
        $houseExtra = HousesExtra::where('house_id', $id)->first();
        $houseExtra->description = $request->input('description', '');
        $houseExtra->full_address = $city->name . $area->name . $request->input('address', '');
        $houseExtra->save();

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }

    /**
     * 刪除自己刊登的房屋
     *
     * @param Request $request
     * @param int $id
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
        if (!$user || $user->id !== $house->user_id) {
            return response()->json(['success' => false, 'message' => 'MSG_PERMISSION_DENY', 'data' => ''], 403);
        }

        // 刪除 house
        $house->delete();

        return response()->json(['success' => true, 'message' => '', 'data' => '']);
    }
}
