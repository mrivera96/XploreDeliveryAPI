<?php

namespace App\Http\Controllers;

use App\Category;
use App\DeliveryClient;
use App\Tarifa;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriesController extends Controller
{
    public function listCategories()
    {
        try {
            $categories = Category::where('isActivo', 1)->get();
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
            $categories = Category::all();
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

    public function getCustomerCategories(){
        try {
            $currCust = Auth::user()->idCliente;
            $tarCust = DeliveryClient::find($currCust)->rates;
            if($tarCust->count() > 0){
                $idArray= [];
                foreach ($tarCust as $item) {
                    if(!in_array($item->idCategoria, $idArray)){
                        array_push($idArray, $item->idCategoria);
                    }
                }
                $categories = Category::where('isActivo', 1)->whereIn('idCategoria', $idArray)->get();
            }else{
                $categories = Category::where('isActivo', 1)->get();
            }

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

    public function createCategory(Request $request){
        $rCat = $request->form;
        try {
            $nCategory = new Category();
            $nCategory->descCategoria = $rCat['descCategoria'];
            $nCategory->fechaAlta = Carbon::now();
            $nCategory->save();
            return response()->json([
                'error' => 0,
                'message' => 'Categoría agregada correctamente.'
            ],200);

        }catch (Exception $ex){
            return response()->json([
                'error' => 1,
                'message' => 'Error al agregar la categoría.'
            ],500);
        }
    }

    public function updateCategory(Request $request){
        $idCat = $request->form["idCategoria"];
        try {
            $currCat = Category::where('idCategoria', $idCat);
            $currCat->update([
                'descCategoria' => $request->form["descCategoria"],
                'isActivo' => $request->form['isActivo']
                ]);
            return response()->json([
                'error' => 0,
                'message' => 'Categoría actualizada correctamente.'
            ],200);
        }catch (Exception $ex){
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar la categoría.'
            ],500);
        }
    }


}
