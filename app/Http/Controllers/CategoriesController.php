<?php

namespace App\Http\Controllers;

use App\Category;
use App\DeliveryClient;
use App\RateCustomer;
use App\Tarifa;
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
            $tarCust = RateCustomer::where('idCliente', $currCust)->get();

            if ($tarCust->count() > 0) {
                $onlyConsolidated = RateCustomer::where('idCliente', $currCust)
                    ->whereHas('rate', function ($q) {
                        $q->where('idTipoTarifa', 2);
                    })->count();

                if ($onlyConsolidated == $tarCust->count()) {
                    $idArray = [];
                    foreach ($tarCust as $item) {
                        if (!in_array($item->rate->idCategoria, $idArray) && $item->rate->idTipoTarifa == 1) {
                            array_push($idArray, $item->rate->idCategoria);
                        }
                    }

                    $categories = Category::where('isActivo', 1)->orderBy('orden')->get();

                    $consolidatedRates = RateCustomer::where('idCliente', $currCust)
                        ->whereHas('rate', function ($q) {
                            $q->where('idTipoTarifa', 2);
                        })->get();
                    if ($consolidatedRates->count() > 0) {
                        $idArray = [];
                        foreach ($consolidatedRates as $item) {
                            if (!in_array($item->rate->idCategoria, $idArray)) {
                                array_push($idArray, $item->rate->idCategoria);
                            }
                        }
                    }

                    $consolidatedCategories = Category::with(['rate.schedules', 'rate.rateDetail', 'rate.consolidatedDetail'])
                        ->where('isActivo', 1)
                        ->whereIn('idCategoria', $idArray)
                        ->orderBy('orden')->get();



                    return response()->json([
                        'error' => 0,
                        'data' => $categories,
                        'consolidatedCategories' => $consolidatedCategories
                    ], 200);
                } else {
                    $idArray = [];
                    foreach ($tarCust as $item) {
                        if (!in_array($item->rate->idCategoria, $idArray) && $item->rate->idTipoTarifa == 1) {
                            array_push($idArray, $item->rate->idCategoria);
                        }
                    }

                    $categories = Category::with(['rate.schedules', 'rate.rateDetail', 'rate.consolidatedDetail'])
                        ->where('isActivo', 1)
                        ->whereIn('idCategoria', $idArray)
                        ->orderBy('orden')->get();

                    $consolidatedRates = RateCustomer::where('idCliente', $currCust)
                        ->whereHas('rate', function ($q) {
                            $q->where('idTipoTarifa', 2);
                        })->get();
                    if ($consolidatedRates->count() > 0) {
                        $idArray = [];
                        foreach ($consolidatedRates as $item) {
                            if (!in_array($item->rate->idCategoria, $idArray)) {
                                array_push($idArray, $item->rate->idCategoria);
                            }
                        }
                    }

                    $consolidatedCategories = Category::with(['rate.schedules', 'rate.rateDetail', 'rate.consolidatedDetail'])
                        ->where('isActivo', 1)
                        ->whereIn('idCategoria', $idArray)
                        ->orderBy('orden')->get();

                    $schedules = [];

                    foreach ($consolidatedCategories as $category) {
                        $rateSchedules = $category->rate->schedules->sortBy('cod');

                        $today = Carbon::now()->dayOfWeek;
                        $datesToShow = [];

                        foreach ($rateSchedules as $schedule) {
                            if ($schedule->cod != $today) {
                                $day = jddayofweek($schedule->cod - 1 , 1);

                                $closestDate = strtotime("next " . $day . "", strtotime(Carbon::now()));
                                $date = (object) array();
                                $date->date = Carbon::parse($closestDate)->format('Y-m-d');
                                $date->day = utf8_encode(strtolower($schedule->dia));
                                $date->cod = $schedule->cod;
                                $date->label = $schedule->dia . ' ' . Carbon::parse($closestDate)->format('Y-m-d');

                                $exists = 0;
                                foreach ($datesToShow as $datets) {
                                    if ($datets->day == $date->day) {
                                        $exists++;
                                    }
                                }

                                if ($exists == 0) {
                                    array_push($datesToShow, $date);
                                }

                            } else if ($schedule->cod == $today && $schedule->inicio >= date('H:i', strtotime(Carbon::now()))) {
                                $closestDate = $schedule->dia . ' ' . Carbon::parse(Carbon::now())->format('Y-m-d');
                                $date = (object)array(
                                    'date' => Carbon::parse(Carbon::now())->format('Y-m-d'),
                                    'day' => strtolower($schedule->dia),
                                    'cod' => $schedule->cod,
                                    'label' => $closestDate
                                );
                                $exists = 0;
                                foreach ($datesToShow as $datets) {
                                    if ($datets->day == $date->day) {
                                        $exists++;
                                    }
                                }

                                if ($exists == 0 ) {
                                    array_push($datesToShow, $date);
                                }
                            }

                        }

                        $category->datesToShow = $datesToShow;

                    }

                    return response()->json([
                        'error' => 0,
                        'data' => $categories,
                        'consolidatedCategories' => $consolidatedCategories
                    ], 200);
                }


            } else {
                $categories = Category::where('isActivo', 1)->orderBy('orden')->get();
                return response()->json([
                    'error' => 0,
                    'data' => $categories
                ], 200);
            }

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
