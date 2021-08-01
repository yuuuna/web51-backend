<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'api/v1'], function () {
    // user 相關
    Route::group(['prefix' => '/user'], function () {
        // 1. 使用者登入
        Route::post('/login', [AuthController::class, 'login']);

        // 2. 使用者登出
        Route::post('/logout', [AuthController::class, 'logout']);

        // 3. 使用者註冊
        Route::post('/register', [AuthController::class, 'register']);
    });

    // house 相關
    Route::group(['prefix' => '/house'], function () {
        // 6. 刊登房屋
        Route::post('/', [HouseController::class, 'store']);

        // 7. 刪除自己刊登的房屋
        Route::put('/{id}', [HouseController::class, 'update']);

        // 8. 刪除自己刊登的房屋
        Route::delete('/{id}', [HouseController::class, 'destroy']);
    });
});
