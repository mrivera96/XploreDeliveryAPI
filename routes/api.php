<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'categories'], function () {
    Route::get('list', 'CategoriesController@listCategories');
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login');
    Route::get('logout', 'AuthController@logout');
});

Route::group(['prefix' => 'deliveries'], function () {
    Route::post('new', 'DeliveriesController@createDelivery');
    Route::get('getTarifas', 'DeliveriesController@getTarifas');
    Route::get('list', 'DeliveriesController@list');
    Route::get('getById', 'DeliveriesController@getById');

});

Route::group(['prefix' => 'deliveries'], function () {
    Route::post('new', 'DeliveriesController@createDelivery');
});
