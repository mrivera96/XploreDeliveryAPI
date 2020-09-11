<?php

use App\Http\Controllers\DeliveryUsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

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


/****************************
 * RUTAS PARA WS COMPARTIDOS
 * ***************************/

//WS de autenticación
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login');
    Route::post('passwordRecovery', 'AuthController@passwordRecovery');
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', 'AuthController@logout');
    });
});

//WS que NO requieren autenticación

Route::group(['prefix' => 'shared'], function () {
    //Rutas Horarios
    Route::group(['prefix' => 'schedule'], function () {
        Route::get('get', 'ScheduleController@getSchedules');
    });

    //Rutas Estados
    Route::group(['prefix' => 'states'], function () {
        Route::get('list', 'StatesController@list');
    });

    //Rutas Tarifas
    Route::group(['prefix' => 'rates'], function () {
        Route::get('get', 'RatesController@getRates');
    });

    //Rutas Categorias
    Route::group(['prefix' => 'categories'], function () {
        Route::get('showAll', 'CategoriesController@showAllCategories');
    });
    //Rutas Recargos
    Route::group(['prefix' => 'surcharges'], function () {
        Route::get('get', 'SurchargesController@getSurcharges');
    });

    //WS que SI requieren autenticación
    Route::group(['middleware' => 'auth:api'], function () {

        //Rutas Deliveries
        Route::group(['prefix' => 'deliveries'], function () {
            Route::post('getById', 'DeliveriesController@getById');
        });

        //Rutas Pagos
        Route::group(['prefix' => 'payments'], function () {
            Route::post('list', 'PaymentController@getPayments');
        });


    });
});


/*************************
 * RUTAS PARA WS DE ADMINS
 * ************************/

Route::group(['prefix' => 'admins'], function () {
    //WS que requieren autenticación
    Route::group(['middleware' => 'auth:api'], function () {
        //Rutas Cargos Extras
        Route::group(['prefix' => 'extraCharges'], function () {
            Route::post('get', 'ExtraChargesController@get');
            Route::post('create', 'ExtraChargesController@create');
            Route::post('update', 'ExtraChargesController@update');
            Route::post('getCategories', 'ExtraChargesController@getExtraChargeCategories');
            Route::post('removeCategory', 'ExtraChargesController@removeCategory');
            Route::post('addCategory', 'ExtraChargesController@addCategory');
            Route::post('getOptions', 'ExtraChargesController@getExtraChargeOptions');
            Route::post('removeOption', 'ExtraChargesController@removeOption');
            Route::post('addOption', 'ExtraChargesController@addOption');
        });

        //Rutas Deliveries
        Route::group(['prefix' => 'deliveries'], function () {
            Route::post('getToday', 'DeliveriesController@getTodayDeliveries');
            Route::post('getTomorrow', 'DeliveriesController@getTomorrowDeliveries');
            Route::post('getAll', 'DeliveriesController@getAllDeliveries');
            Route::post('assign', 'DeliveriesController@assignDelivery');
            Route::post('finish', 'DeliveriesController@finishDelivery');
            Route::post('changeState', 'DeliveriesController@changeStateDelivery');
            Route::post('getPending', 'DeliveriesController@getPendingDeliveries');
        });

        //Rutas Envios
        Route::group(['prefix' => 'orders'], function () {
            Route::post('changeState', 'DeliveriesController@changeOrderState');
            Route::post('getToday', 'DeliveriesController@getTodayOrders');
            Route::post('getAll', 'DeliveriesController@getAllOrders');
            Route::post('getOrdersByCustomer', 'DeliveriesController@getOrdersByCustomer');
            Route::post('assign', 'DeliveriesController@assignOrder');
            Route::post('addExtracharge', 'DeliveriesController@addOrderExtracharge');
            Route::post('removeExtracharge', 'DeliveriesController@removeOrderExtracharge');
            Route::post('filter', 'DeliveriesController@getFilteredOrders');
        });

        //Rutas Tarifas
        Route::group(['prefix' => 'rates'], function () {
            Route::post('update', 'RatesController@updateRate');
            Route::post('create', 'RatesController@createRate');
            Route::post('removeCustomer', 'RatesController@removeCustomer');
            Route::post('getCustomers', 'RatesController@getCustomers');
            Route::post('addCustomer', 'RatesController@addCustomer');
            Route::post('updateDetail', 'RatesController@updateRateDetail');
            Route::post('removeSchedule', 'RatesController@removeSchedule');
            Route::post('getRateSchedules', 'RatesController@getSchedules');

        });

        //Rutas Tipo Tarifas
        Route::group(['prefix' => 'ratesType'], function () {
            Route::post('get', 'RateTypeController@get');
        });

        //Rutas Recargos
        Route::group(['prefix' => 'surcharges'], function () {
            Route::post('update', 'SurchargesController@updateSurcharge');
            Route::post('create', 'SurchargesController@createSurcharge');
        });

        //Rutas Categorias
        Route::group(['prefix' => 'categories'], function () {
            Route::get('get', 'CategoriesController@listCategories');
            Route::post('update', 'CategoriesController@updateCategory');
            Route::post('create', 'CategoriesController@createCategory');
        });

        //Rutas Conductores
        Route::group(['prefix' => 'drivers'], function () {
            Route::post('get', 'UsersController@listDrivers');
            Route::post('create', 'UsersController@createDriver');
            Route::post('update', 'UsersController@updateDriver');
        });

        //Rutas Ciudades
        Route::group(['prefix' => 'cities'], function () {
            Route::post('get', 'AgenciesController@listCities');
        });

        //Rutas Agencias
        Route::group(['prefix' => 'agencies'], function () {
            Route::post('list', 'AgenciesController@listAgencies');
        });

        //Rutas Reportes
        Route::group(['prefix' => 'reports'], function () {
            Route::post('ordersByDriver', 'DeliveriesController@reportOrdersByDriver');
            Route::post('ordersByCustomer', 'DeliveriesController@reportOrdersByCustomer');
            Route::post('deliveriesReport', 'DeliveriesController@deliveriesReport');
            Route::post('paymentsReport', 'PaymentController@getPaymentsReport');
            Route::post('customersBalanceReport', 'DeliveryUsersController@getCustomersBalanceReport');
        });

        //Rutas Pagos
        Route::group(['prefix' => 'payments'], function () {
            Route::post('create', 'PaymentController@createPayment');
            Route::post('listTypes', 'PaymentController@getPaymentTypes');
        });

        //Rutas Horarios
        Route::group(['prefix' => 'schedule'], function () {
            Route::post('update', 'ScheduleController@updateSchedule');
        });

        //Rutas Clientes
        Route::group(['prefix' => 'customers'], function () {
            Route::post('get', 'DeliveryUsersController@list');
            Route::post('new', 'DeliveryUsersController@newCustomer');
            Route::post('update', 'DeliveryUsersController@updateCustomer');
            Route::post('changePassword', 'DeliveryUsersController@changePassword');
        });

    });

});


Route::get('generatePassword','AuthController@generatePassword');
Route::get('testPassword','AuthController@testPassword');
/***************************
 * RUTAS PARA WS DE CLIENTES
 * ***************************/

//WS que requieren autenticación

Route::group(['middleware' => 'auth:api'], function () {
    Route::group(['prefix' => 'customers'], function () {
        //Ruta Dashboard
        Route::post('myDashboard', 'DeliveryUsersController@dashboardData');

        //Ruta Balance
        Route::post('balance', 'DeliveryUsersController@customerBalance');

        //Rutas Deliveries
        Route::group(['prefix' => 'deliveries'], function () {
            Route::post('changeHour', 'DeliveriesController@changeDeliveryHour');
            Route::post('getToday', 'DeliveriesController@getTodayCustomerDeliveries');
            Route::post('getAll', 'DeliveriesController@getAllCustomerDeliveries');
            Route::post('new', 'DeliveriesController@createCustomerDelivery');
        });

        //Rutas Direcciones
        Route::group(['prefix' => 'address'], function () {
            Route::post('get', 'BranchOfficesController@getCustomerBranchOffices');
            Route::post('new', 'BranchOfficesController@newBranch');
            Route::post('update', 'BranchOfficesController@updateBranch');
            Route::post('delete', 'BranchOfficesController@deleteBranch');
        });

        //Rutas Tarifas
        Route::group(['prefix' => 'rates'], function () {
            Route::post('get', 'RatesController@getCustomerRates');
        });

        //Rutas Envios
        Route::group(['prefix' => 'orders'], function () {
            Route::post('getToday', 'DeliveriesController@getTodayCustomerOrders');
            Route::post('getAll', 'DeliveriesController@getAllCustomerOrders');
        });

        //Rutas Recargos
        Route::group(['prefix' => 'surcharges'], function () {
            Route::post('get', 'SurchargesController@getCustomerSurcharges');
        });

        //Rutas Categorias
        Route::group(['prefix' => 'categories'], function () {
            Route::post('get', 'CategoriesController@getCustomerCategories');
        });

        //Rutas Pagos
        Route::group(['prefix' => 'payments'], function () {
            Route::post('get', 'PaymentController@getCustomerPayments');
        });

    });
});


/***************************
 * RUTAS PARA WS DE TESTEO
 * ***************************/

Route::get('testCript', 'DeliveryUsersController@testEncryption');
Route::get('testDeCript', 'DeliveryUsersController@testDecryption');
Route::get('testAuthCript', 'AuthController@testGettingCript');
Route::post('testReport', 'DeliveriesController@reportOrdersByDriver');
Route::post('sendMail', 'DeliveriesController@resendMail');
