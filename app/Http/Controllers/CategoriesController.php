<?php

namespace App\Http\Controllers;

use App\Category;
use Exception;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function listCategories()
    {
        try {
            $categories = Category::where('delivery', 1)->get(['idTipoVehiculo', 'descTipoVehiculo', 'delivery']);
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

    public function showAllCategories()
    {
        try {
            $categories = Category::all(['idTipoVehiculo', 'descTipoVehiculo', 'delivery']);
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

    public function updateCategory(Request $request){
        $idCat = $request->form["idTipoVehiculo"];
        $permDelivery = $request->form["delivery"];
        try {
            $currCat = Category::where('idTipoVehiculo', $idCat);
            $currCat->update(['delivery' => $permDelivery]);
            return response()->json([
                'error' => 0,
                'message' => 'Categoría actualizada correctamente.'
            ],200);
        }catch (Exception $ex){
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar la categoría. '.$ex->getMessage()
            ],500);
        }
    }


}
