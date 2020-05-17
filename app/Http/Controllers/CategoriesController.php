<?php

namespace App\Http\Controllers;

use App\Category;
use Exception;

class CategoriesController extends Controller
{
    public function listCategories()
    {
        try {
            $categories = Category::whereIn('idTipoVehiculo', [1,3,6])->get(['idTipoVehiculo', 'descTipoVehiculo']);
            return response()->json([
                'error' => 0,
                'data' => $categories
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()
            ], 500);
        }
    }


}
