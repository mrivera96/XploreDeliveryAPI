<?php

namespace App\Http\Controllers;

use App\Category;
use App\RateCustomer;
use App\RecargoDelivery;
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

    public function getCustomerCategories(Request $request)
    {
        try {
            if ($request->idCustomer == null) {
                $currCust = Auth::user()->idCliente;
            } else {
                $currCust = $request->idCustomer;
            }

            $tarCust = RateCustomer::where('idCliente', $currCust)->get();

            if ($tarCust->count() > 0) {

                $categories = Category::where('isActivo', 1)
                    ->whereHas('rate', function ($q) use ($currCust) {
                        $q->where(['idTipoTarifa' => 1, 'idCliente' => NULL])
                            ->whereHas('rateDetail', function ($q) use ($currCust) {
                                $q->where('idCliente', $currCust);
                            });
                    })
                    ->orderBy('orden')
                    ->get();

                if ($categories->count() == 0) {
                    $categories = Category::where('isActivo', 1)
                        ->whereHas('rate', function ($q) {
                            $q->where(['idTipoTarifa' => 1, 'idCliente' => 1]);
                        })
                        ->orderBy('orden')
                        ->get();
                }

                $routingCategories = Category::where('isActivo', 1)
                    ->whereHas('rate', function ($q) use ($currCust) {
                        $q->where(['idTipoTarifa' => 3, 'idCliente' => NULL])
                            ->whereHas('rateDetail', function ($q) use ($currCust) {
                                $q->where('idCliente', $currCust);
                            });
                    })
                    ->orderBy('orden')
                    ->get();

                if ($routingCategories->count() == 0) {
                    $routingCategories =  Category::where('isActivo', 1)
                        ->whereHas('rate', function ($q) {
                            $q->where(['idTipoTarifa' => 3, 'idCliente' => 1]);
                        })
                        ->orderBy('orden')
                        ->get();
                }

                $consolidatedCategories = Category::with([
                    'rate.schedules',
                    'rate.rateDetail',
                    'rate.consolidatedDetail'
                ])
                    ->where('isActivo', 1)
                    ->whereHas('rate', function ($q) use ($currCust) {
                        $q->where(['idTipoTarifa' => 2, 'idCliente' => NULL])
                            ->whereHas('rateDetail', function ($q) use ($currCust) {
                                $q->where('idCliente', $currCust);
                            });
                    })
                    ->orderBy('orden')
                    ->get();

                if ($consolidatedCategories->count() == 0) {
                    $consolidatedCategories = Category::with([
                        'rate.schedules',
                        'rate.rateDetail',
                        'rate.consolidatedDetail'
                    ])
                        ->where('isActivo', 1)
                        ->whereHas('rate', function ($q) {
                            $q->where(['idTipoTarifa' => 2, 'idCliente' => 1]);
                        })
                        ->orderBy('orden')
                        ->get();
                }

                $consolidatedForeignCategories = Category::with([
                    'rate.schedules',
                    'rate.rateDetail',
                    'rate.consolidatedDetail'
                ])
                    ->where('isActivo', 1)
                    ->whereHas('rate', function ($q) use ($currCust) {
                        $q->where(['idTipoTarifa' => 4, 'idCliente' => NULL])
                            ->whereHas('rateDetail', function ($q) use ($currCust) {
                                $q->where('idCliente', $currCust);
                            });
                    })
                    ->orderBy('orden')
                    ->get();

                if ($consolidatedForeignCategories->count() == 0) {
                    $consolidatedForeignCategories = Category::with([
                        'rate.schedules',
                        'rate.rateDetail',
                        'rate.consolidatedDetail'
                    ])
                        ->where('isActivo', 1)
                        ->whereHas('rate', function ($q) {
                            $q->where(['idTipoTarifa' => 4, 'idCliente' => 1]);
                        })
                        ->orderBy('orden')
                        ->get();
                }
            } else {
                $categories = Category::where('isActivo', 1)
                    ->whereHas('rate', function ($q) {
                        $q->where(['idTipoTarifa' => 1, 'idCliente' => 1]);
                    })
                    ->orderBy('orden')
                    ->get();

                $consolidatedCategories = Category::with([
                    'rate.schedules',
                    'rate.rateDetail',
                    'rate.consolidatedDetail'
                ])
                    ->where('isActivo', 1)
                    ->whereHas('rate', function ($q) {
                        $q->where(['idTipoTarifa' => 2, 'idCliente' => 1]);
                    })
                    ->orderBy('orden')
                    ->get();

                $routingCategories = Category::where('isActivo', 1)
                    ->whereHas('rate', function ($q) {
                        $q->where(['idTipoTarifa' => 3, 'idCliente' => 1]);
                    })
                    ->orderBy('orden')
                    ->get();

                $consolidatedForeignCategories = Category::with([
                    'rate.schedules',
                    'rate.rateDetail',
                    'rate.consolidatedDetail'
                ])
                    ->where('isActivo', 1)
                    ->whereHas('rate', function ($q) {
                        $q->where(['idTipoTarifa' => 4, 'idCliente' => 1]);
                    })
                    ->orderBy('orden')
                    ->get();
            }

            foreach ($consolidatedCategories as $category) {
                $category->categoryExtraCharges = $category->categoryExtraCharges()
                    ->whereHas('extraCharge', function ($q) {
                        $q->where('tipoCargo', 'F');
                    })
                    ->get();

                foreach ($category->categoryExtraCharges as $cEC) {
                    $cEC->extraCharge->options;
                }

                $customerSurcharges = RecargoDelivery::where([
                    'idCategoria' => $category->idCategoria,
                    'isActivo' => 1,
                    'idTipoEnvio' => 2
                ])
                    ->whereHas('customerSurcharges', function ($q) use ($currCust) {
                        $q->where('idCliente', $currCust);
                    });

                if ($customerSurcharges->count() > 0) {
                    $category->surcharges = $customerSurcharges->get();
                } else {
                    $category->surcharges = RecargoDelivery::where([
                        'idCategoria' => $category->idCategoria,
                        'isActivo' => 1,
                        'idTipoEnvio' => 2
                    ])
                        ->where('idCliente', 1)
                        ->get();
                }

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
                        if ($rate->idTipoTarifa == 2) {
                            array_push($ratesToShow, $rate);
                        }
                    }

                    $dates = array();
                    foreach ($datesToShow as $my_object) {
                        $dates[] = $my_object->date; //any object field
                    }

                    foreach ($datesToShow as $date) {
                        $hoursToShow = [];
                        foreach ($rate->schedules as $schedule) {
                            if ($schedule->cod == $date->cod) {

                                $hour = (object)array();
                                $hour->hour = Carbon::parse('2020-8-18 ' . $schedule->inicio)->format('H:i');
                                $hour->label = Carbon::parse('2020-8-18 ' . $schedule->inicio)->format('h:i a');

                                $datetime = $date->date . ' ' . $hour->hour;
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

            foreach ($consolidatedForeignCategories as $category) {
                $category->categoryExtraCharges = $category->categoryExtraCharges()
                    ->whereHas('extraCharge', function ($q) {
                        $q->where('tipoCargo', 'F');
                    })->get();

                foreach ($category->categoryExtraCharges as $cEC) {
                    $cEC->extraCharge->options;
                }

                $customerSurcharges = RecargoDelivery::where([
                    'idCategoria' => $category->idCategoria,
                    'isActivo' => 1,
                    'idTipoEnvio' => 4
                ])
                    ->whereHas('customerSurcharges', function ($q) use ($currCust) {
                        $q->where('idCliente', $currCust);
                    });

                if ($customerSurcharges->count() > 0) {
                    $category->surcharges = $customerSurcharges->get();
                } else {
                    $category->surcharges = RecargoDelivery::where([
                        'idCategoria' => $category->idCategoria,
                        'isActivo' => 1,
                        'idTipoEnvio' => 4
                    ])
                        ->where('idCliente', 1)
                        ->get();
                }

                $rates = $category->rate;
                $ratesToShow = [];
                foreach ($rates as $rate) {
                    $today = Carbon::now()->dayOfWeek;
                    $datesToShow = [];

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
                    if ($rate->idTipoTarifa == 4) {
                        array_push($ratesToShow, $rate);
                    }

                    $dates = array();
                    foreach ($datesToShow as $my_object) {
                        $dates[] = $my_object->date; //any object field
                    }

                    foreach ($datesToShow as $date) {
                        $hoursToShow = [];
                        foreach ($rate->schedules as $schedule) {
                            if ($schedule->cod == $date->cod) {

                                $hour = (object)array();
                                $hour->hour = Carbon::parse('2020-8-18 ' . $schedule->inicio)->format('H:i');
                                $hour->label = Carbon::parse('2020-8-18 ' . $schedule->inicio)->format('h:i a');

                                $datetime = $date->date . ' ' . $hour->hour;
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

            foreach ($categories as $category) {
                $category->categoryExtraCharges = $category->categoryExtraCharges()
                    ->whereHas('extraCharge', function ($q) {
                        $q->where('tipoCargo', 'F');
                    })
                    ->get();

                $customerSurcharges = RecargoDelivery::where([
                    'idCategoria' => $category->idCategoria,
                    'isActivo' => 1,
                    'idTipoEnvio' => 1
                ])
                    ->whereHas('customerSurcharges', function ($q) use ($currCust) {
                        $q->where('idCliente', $currCust);
                    });

                if ($customerSurcharges->count() > 0) {
                    $category->surcharges = $customerSurcharges->get();
                } else {
                    $category->surcharges = RecargoDelivery::where([
                        'idCategoria' => $category->idCategoria,
                        'isActivo' => 1,
                        'idTipoEnvio' => 1
                    ])
                        ->where('idCliente', 1)
                        ->get();
                }

                foreach ($category->categoryExtraCharges as $cEC) {
                    $cEC->extraCharge->options;
                }
            }

            foreach ($routingCategories as $category) {
                $category->categoryExtraCharges = $category->categoryExtraCharges()
                    ->whereHas('extraCharge', function ($q) {
                        $q->where('tipoCargo', 'F');
                    })
                    ->get();

                $customerSurcharges = RecargoDelivery::where([
                    'idCategoria' => $category->idCategoria,
                    'isActivo' => 1,
                    'idTipoEnvio' => 3
                ])
                    ->whereHas('customerSurcharges', function ($q) use ($currCust) {
                        $q->where('idCliente', $currCust);
                    });

                if ($customerSurcharges->count() > 0) {
                    $category->surcharges = $customerSurcharges->get();
                } else {
                    $category->surcharges = RecargoDelivery::where([
                        'idCategoria' => $category->idCategoria,
                        'isActivo' => 1,
                        'idTipoEnvio' => 3
                    ])
                        ->where('idCliente', 1)
                        ->get();
                }

                foreach ($category->categoryExtraCharges as $cEC) {
                    $cEC->extraCharge->options;
                }

                $rates = $category->rate;

                $ratesToShow = [];
                foreach ($rates as $rate) {
                    $detail = $rate->rateDetail;
                    if (sizeof($detail) > 0) {
                        $existsCustomer = 0;
                        foreach ($detail as $dtl) {
                            if ($dtl->idCliente == $currCust) {
                                $existsCustomer++;
                            }
                        }
                        if ($existsCustomer > 0 && $rate->idTipoTarifa == 3) {
                            array_push($ratesToShow, $rate);
                        }
                    } else {
                        if ($rate->idTipoTarifa == 3) {
                            array_push($ratesToShow, $rate);
                        }
                    }
                }
                $category->ratesToShow = $ratesToShow;
            }
            return response()->json([
                'error' => 0,
                'data' => $categories,
                'consolidatedCategories' => $consolidatedCategories,
                'consolidatedForeignCategories' => $consolidatedForeignCategories,
                'routingCategories' => $routingCategories,
                'demand' => 'HORARIO NAVIDEÑO: Estimado cliente, comunicamos que el día 24 de Diciembre atenderemos pedidos en horario especial de 08:00am a 3:00pm; el 25 de Diciembre nuestra plataforma permanecerá cerrada. ¡Feliz Navidad!' /* 'Estimado cliente, comunicamos que estamos experimentando una alta demanda en todas nuestras
                categorías y mayor tráfico en la ciudad debido a la temporada.
                Agradecemos de antemano su comprensión ante cualquier atraso o inconveniente.' */
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al cargar los datos' //$ex->getMessage()
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
