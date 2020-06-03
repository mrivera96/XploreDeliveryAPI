<?php


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

Route::get('viewTest/{idCliente}', 'DeliveryUsersController@testAccessDetails');
Route::get('viewTesting/{idDelivery}', 'DeliveriesController@testReserveFormat');
Route::get('testSendMail/{idDelivery}', 'DeliveriesController@testSendMail');

