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
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('showAll', 'CategoriesController@showAllCategories');
        Route::post('update', 'CategoriesController@updateCategory');
        Route::post('create', 'CategoriesController@createCategory');
    });

});

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login');
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', 'AuthController@logout');
    });

});

Route::group(['prefix' => 'deliveries'], function () {
    Route::post('new', 'DeliveriesController@createDelivery');
    Route::get('getTarifas', 'RatesController@getRates');
    Route::get('getRecargos', 'SurchargesController@getSurcharges');

    Route::post('entregar', 'DeliveriesController@updateDeliveried');
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('getById', 'DeliveriesController@getById');
        Route::post('list', 'DeliveriesController@list');
        Route::post('assign', 'DeliveriesController@assignDelivery');
        Route::post('finish', 'DeliveriesController@finishDelivery');
        Route::post('changeState', 'DeliveriesController@changeStateDelivery');
        Route::post('getOrders', 'DeliveriesController@getOrders');
        Route::post('getPendingDeliveries', 'DeliveriesController@getPendingDeliveries');
    });

});

Route::group(['prefix' => 'rates'], function () {
    Route::post('update', 'RatesController@updateRate');
    Route::post('create', 'RatesController@createRate');
});

Route::group(['prefix' => 'surcharges'], function () {
    Route::post('update', 'SurchargesController@updateSurcharge');
    Route::post('create', 'SurchargesController@createSurcharge');
});

Route::group(['prefix' => 'states'], function () {
    Route::get('list', 'StatesController@list');
});

Route::group(['prefix' => 'vehicles'], function () {
    Route::get('list', 'VehiclesController@list');
});
Route::group(['prefix' => 'drivers'], function () {
    Route::get('list', 'UsersController@listDrivers');
});

Route::group(['prefix' => 'customers'], function () {
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('getMyDeliveries', 'DeliveriesController@getCustomerDeliveries');
        Route::post('getMyBranchOffices', 'BranchOfficesController@getCustomerBranchOffices');
        Route::post('newCustomerDelivery', 'DeliveriesController@createCustomerDelivery');
        Route::post('getCustomerOrders', 'DeliveriesController@getCustomerOders');
        Route::post('list', 'DeliveryUsersController@list');
        Route::post('new', 'DeliveryUsersController@newCustomer');
        Route::post('update', 'DeliveryUsersController@updateCustomer');
        Route::post('newBranch', 'BranchOfficesController@newBranch');
        Route::post('changePassword', 'DeliveryUsersController@changePassword');
        Route::post('updateBranch', 'BranchOfficesController@updateBranch');
        Route::post('deleteBranch', 'BranchOfficesController@deleteBranch');
        Route::post('getMyRates', 'RatesController@getCustomerRates');
        Route::post('getMySurcharges', 'SurchargesController@getCustomerSurcharges');
        Route::post('getMyCategories', 'CategoriesController@getCustomerCategories');
    });
});

Route::get('testCript', 'DeliveryUsersController@testEncryption');
Route::get('testDeCript', 'DeliveryUsersController@testDecryption');
Route::get('testAuthCript', 'AuthController@testGettingCript');
