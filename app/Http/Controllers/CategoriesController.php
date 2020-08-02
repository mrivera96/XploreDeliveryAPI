<?php

namespace App\Http\Controllers;

use App\Category;
use App\DeliveryClient;
use App\RateCustomer;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CategoriesController extends Controller
{
    public function listCategories()
    {
        try {
            $categories = Category::where('isActivo', 1)->get();
            return response()->json(
                [
                    'error' => 0,
                    'data' => $categories
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
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
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()
            ], 500);
        }
    }

    public function getCustomerCategories()
    {
        try {
            $currCust = Auth::user()->idCliente;
            $tarCust = RateCustomer::where('idCliente',$currCust)->get();
            if ($tarCust->count() > 0) {
                $idArray = [];
                foreach ($tarCust as $item) {
                    if (!in_array($item->rate->idCategoria, $idArray)) {
                        array_push($idArray, $item->rate->idCategoria);
                    }
                }
                $categories = Category::with('categoryExtraCharges.extraCharge.options')
                    ->where('isActivo', 1)->whereIn('idCategoria', $idArray)
                    ->orderBy('orden')->get();
            } else {
                $categories = Category::with('categoryExtraCharges.extraCharge.options')
                    ->where('isActivo', 1)->orderBy('orden')->get();
            }

            return response()->json([
                'error' => 0,
                'data' => $categories
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()
            ], 500);
        }
    }

    public function createCategory(Request $request)
    {
        $request->validate(
            [
                'form' => 'required',
                'form.descCategoria' => 'required',
                'form.descripcion' => 'required'
            ]
        );
        $rCat = $request->form;
        try {
            $nCategory = new Category();
            $nCategory->descCategoria = $rCat['descCategoria'];
            $nCategory->descripcion = $rCat['descripcion'];
            $nCategory->fechaAlta = Carbon::now();
            $nCategory->save();
            return response()->json([
                'error' => 0,
                'message' => 'Categoría agregada correctamente.'
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al agregar la categoría.'
            ], 500);
        }
    }

    public function updateCategory(Request $request)
    {
        $request->validate(
            [
                'form' => 'required',
                'form.idCategoria' => 'required',
                'form.descCategoria' => 'required',
                'form.descripcion' => 'required'
            ]
        );
        $idCat = $request->form["idCategoria"];
        try {
            $currCat = Category::where('idCategoria', $idCat);
            $currCat->update([
                'descCategoria' => $request->form["descCategoria"],
                'descripcion' => $request->form["descripcion"],
                'isActivo' => $request->form['isActivo']
            ]);
            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Categoría actualizada correctamente.'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Error al actualizar la categoría.'
                ],
                500
            );
        }
    }

}
