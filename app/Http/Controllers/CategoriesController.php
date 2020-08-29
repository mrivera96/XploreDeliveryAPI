<?php

namespace App\Http\Controllers;

use App\Category;
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
            $tarCust = RateCustomer::where('idCliente', $currCust)->get();

            if ($tarCust->count() > 0) {
                $onlyConsolidated = RateCustomer::where('idCliente', $currCust)
                    ->whereHas('rate', function ($q) {
                        $q->where('idTipoTarifa', 2);
                    })->count();

                if ($onlyConsolidated == $tarCust->count()) {

                    $categories = Category::with([
                        'categoryExtraCharges.extraCharge.options',
                        'surcharges'])
                        ->where('isActivo', 1)
                        ->orderBy('orden')
                        ->get();

                    $consolidatedRates = RateCustomer::where('idCliente', $currCust)
                        ->whereHas('rate', function ($q) {
                            $q->where('idTipoTarifa', 2);
                        })->get();

                    $idArray = [];
                    if ($consolidatedRates->count() > 0) {
                        foreach ($consolidatedRates as $item) {
                            if (!in_array($item->rate->idCategoria, $idArray) && $item->rate->idTipoTarifa == 2) {
                                array_push($idArray, $item->rate->idCategoria);
                            }
                        }
                    }

                    $consolidatedCategories = Category::with(['categoryExtraCharges.extraCharge.options',
                        'rate.schedules',
                        'rate.rateDetail',
                        'rate.consolidatedDetail',
                        'surcharges'])
                        ->where('isActivo', 1)
                        ->whereIn('idCategoria', $idArray)
                        ->orderBy('orden')
                        ->get();
                } else {
                    $idArray = [];
                    foreach ($tarCust as $item) {
                        if (!in_array($item->rate->idCategoria, $idArray) && $item->rate->idTipoTarifa == 1) {
                            array_push($idArray, $item->rate->idCategoria);
                        }
                    }

                    $categories = Category::with(['categoryExtraCharges.extraCharge.options',
                        'rate.schedules',
                        'rate.rateDetail',
                        'rate.consolidatedDetail',
                        'surcharges'])
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
                            if (!in_array($item->rate->idCategoria, $idArray) && $item->rate->idTipoTarifa == 2) {
                                array_push($idArray, $item->rate->idCategoria);
                            }
                        }
                    }

                    $consolidatedCategories = Category::with(['categoryExtraCharges.extraCharge.options',
                        'rate.schedules',
                        'rate.rateDetail',
                        'rate.consolidatedDetail',
                        'surcharges'])
                        ->where('isActivo', 1)
                        ->whereIn('idCategoria', $idArray)
                        ->orderBy('orden')->get();
                }

                foreach ($consolidatedCategories as $category) {
                    $rates = $category->rate;
                    $ratesToShow = [];
                    foreach ($rates as $rate) {
                        $today = Carbon::now()->dayOfWeek;
                        $datesToShow = [];
                        $detail = $rate->rateDetail;
                        $existsCustomer = 0;
                        foreach ($detail as $dtl) {
                            if ($dtl->idCliente == $currCust) {
                                $existsCustomer++;
                            }
                        }
                        if ($existsCustomer > 0) {
                            $rateSchedules = $rate->schedules->sortBy('cod');

                            foreach ($rateSchedules as $schedule) {
                                if ($schedule->cod != $today) {
                                    $day = jddayofweek($schedule->cod - 1, 1);

                                    $closestDate = strtotime("next " . $day . "", strtotime(Carbon::now()));
                                    $date = (object)array();
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
                                        'day' => utf8_encode(strtolower($schedule->dia)),
                                        'cod' => $schedule->cod,
                                        'label' => $closestDate
                                    );
                                    $exists = 0;
                                    foreach ($datesToShow as $datets) {
                                        if ($datets->day == $date->day) {
                                            $exists++;
                                        }
                                    }

                                    if ($exists == 0) {
                                        array_push($datesToShow, $date);
                                    }
                                }

                            }
                            if($rate->idTipoTarifa == 2){
                                array_push($ratesToShow, $rate);
                            }

                        }

                        $dates = array();
                        foreach ($datesToShow as $my_object) {
                            $dates[] = $my_object->date; //any object field
                        }

                        foreach($datesToShow as $date){
                            $hoursToShow = [] ;
                            foreach($rate->schedules as $schedule){
                                if ($schedule->cod == $date->cod) {

                                    $hour = (object) array();
                                    $hour->hour = Carbon::parse('2020-8-18 '. $schedule->inicio)->format('H:i');
                                    $hour->label =  Carbon::parse('2020-8-18 '. $schedule->inicio)->format('h:i a');

                                    $datetime = $date->date. ' '. $hour->hour;
                                    $currentDateTime = Carbon::now();

                                    if ($datetime >= $currentDateTime) {
                                        array_push($hoursToShow, $hour);
                                    }

                                  }
                            }
                            $date->hoursToShow = $hoursToShow;
                        }

                        array_multisort($dates, SORT_ASC, $datesToShow);
                        $rate->datesToShow = $datesToShow;

                    }
                    $category->ratesToShow = $ratesToShow;

                }

                return response()->json([
                    'error' => 0,
                    'data' => $categories,
                    'consolidatedCategories' => $consolidatedCategories
                ], 200);


            } else {
                $categories = Category::with([
                    'categoryExtraCharges.extraCharge.options',
                    'surcharges'])
                    ->where('isActivo', 1)
                    ->orderBy('orden')
                    ->get();

                $consolidatedRates = RateCustomer::where('idCliente', 1)
                    ->whereHas('rate', function ($q) {
                        $q->where('idTipoTarifa', 2);
                    })->get();
                $idArray = [];
                if ($consolidatedRates->count() > 0) {
                    foreach ($consolidatedRates as $item) {
                        if (!in_array($item->rate->idCategoria, $idArray) && $item->rate->idTipoTarifa  == 2) {
                            array_push($idArray, $item->rate->idCategoria);
                        }
                    }
                }

                $consolidatedCategories = Category::with(['categoryExtraCharges.extraCharge.options',
                    'rate.schedules',
                    'rate.rateDetail',
                    'rate.consolidatedDetail',
                    'surcharges'])
                    ->where('isActivo', 1)
                    ->whereIn('idCategoria', $idArray)
                    ->orderBy('orden')
                    ->get();

                foreach ($consolidatedCategories as $category) {
                    $rates = $category->rate;
                    $ratesToShow = [];
                    foreach ($rates as $rate) {
                        $today = Carbon::now()->dayOfWeek;
                        $datesToShow = [];
                        if ($rate->idCliente == 1) {
                            $rateSchedules = $rate->schedules->sortBy('cod');

                            foreach ($rateSchedules as $schedule) {
                                if ($schedule->cod != $today) {
                                    $day = jddayofweek($schedule->cod - 1, 1);

                                    $closestDate = strtotime("next " . $day . "", strtotime(Carbon::now()));
                                    $date = (object)array();
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

                                    if ($exists == 0) {
                                        array_push($datesToShow, $date);
                                    }
                                }

                            }

                            array_push($ratesToShow, $rate);

                        }

                        $dates = array();
                        foreach ($datesToShow as $my_object) {
                            $dates[] = $my_object->date; //any object field
                        }

                        foreach($datesToShow as $date){
                            $hoursToShow = [] ;
                            foreach($rate->schedules as $schedule){
                                if ($schedule->cod == $date->cod) {

                                    $hour = (object) array();
                                    $hour->hour = Carbon::parse('2020-8-18 '. $schedule->inicio)->format('H:i');
                                    $hour->label =  Carbon::parse('2020-8-18 '. $schedule->inicio)->format('h:i a');

                                    $datetime = $date->date. ' '. $hour->hour;
                                    $currentDateTime = Carbon::now();

                                    if ($datetime >= $currentDateTime) {
                                        array_push($hoursToShow, $hour);
                                    }

                                  }
                            }
                            $date->hoursToShow = $hoursToShow;
                        }

                        array_multisort($dates, SORT_ASC, $datesToShow);
                        $rate->datesToShow = $datesToShow;

                    }
                    $category->ratesToShow = $ratesToShow;



                }
                return response()->json([
                    'error' => 0,
                    'data' => $categories,
                    'consolidatedCategories' => $consolidatedCategories
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
