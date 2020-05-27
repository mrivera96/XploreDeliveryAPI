<?php

namespace App\Http\Controllers;

use App\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchOfficesController extends Controller
{
    public function getCustomerBranchOffices(){
        try {
            $myBranchOffices = Branch::where('isActivo', 1)->where('idCliente', Auth::user()->idCliente)->get();
            return response()->json([
                'error' => 0,
                'data' => $myBranchOffices
            ], 200);
        }catch (\Exception $ex){
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()
            ]);
        }
    }
}
