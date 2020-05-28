<?php

namespace App\Http\Controllers;

use App\DeliveryClient;
use App\DeliveryUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryUsersController extends Controller
{
    public function list()
    {
        try {
            $customers = DeliveryClient::all();
            return response()
                ->json([
                    'error' => 0,
                    'data' => $customers
                ], 200);
        }catch (Exception $exception){
            return response()
                ->json([
                    'error' => 1,
                    'message' => $exception->getMessage()
                ], 500);
        }
    }
}
