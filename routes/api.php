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
    Route::group(['middleware' => 'auth:api'], function (){
        Route::post('logout', 'AuthController@logout');
    });

});

Route::group(['prefix' => 'deliveries'], function () {
    Route::post('new', 'DeliveriesController@createDelivery');
    Route::get('getTarifas', 'DeliveriesController@getTarifas');
    Route::get('list', 'DeliveriesController@list');
    Route::get('getById', 'DeliveriesController@getById');
    Route::post('entregar','DeliveriesController@updateDeliveried');
    Route::group(['middleware'=>'auth:api'], function (){
        Route::post('assign','DeliveriesController@assignDelivery');
        Route::post('finish','DeliveriesController@finishDelivery');
        Route::post('changeState','DeliveriesController@changeStateDelivery');
    });

});
Route::group(['prefix' => 'states'], function (){
   Route::get('list', 'StatesController@list');
});

Route::group(['prefix' => 'vehicles'], function () {
    Route::get('list', 'VehiclesController@list');
});
Route::group(['prefix' => 'drivers'], function () {
    Route::get('list', 'UsersController@listDrivers');
});

Route::group(['prefix' => 'customers'], function (){
    Route::post('login', 'DeliveryUsersController@login');
    Route::group(['middleware' => 'auth:customers'], function (){
        Route::post('logout', 'DeliveryUsersController@logout');
        Route::post('getDeliveries', 'DeliveriesController@getCustomerDeliveries');
    });

});
