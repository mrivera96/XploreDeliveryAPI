<?php

namespace App\Http\Controllers;

use App\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    public function getSchedules()
    {
        try {
            $schedules = Schedule::where('idTarifaDelivery',null)->get();
            foreach ($schedules as $schedule) {
                $schedule->inicio = Carbon::parse($schedule->inicio)->format('H:i');
                $schedule->final = Carbon::parse($schedule->final)->format('H:i');
            }

            return response()->json([
                'error' => 0,
                'data' => $schedules
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array([
                'context' => $ex->getTrace()
            ]));

            return response()->json([
                'error' => 1,
                'message' => 'Ha ocurrido un error al obtener los horarios.'
            ]);
        }
    }

    public function updateSchedule(Request $request)
    {
        $request->validate([
            'form' => 'required',
            //'form.descHorario' => 'required',
            'form.scheduleId' => 'required',
            'form.inicio' => 'required',
            'form.final' => 'required',
        ]);

        try {

            $schId = $request->form['scheduleId'];
            $init = date('H:i', strtotime($request->form['inicio']));
            $fin = date('H:i', strtotime($request->form['final']));
            //$schDesc = $request->form['descHorario'];

            $currSch = Schedule::where('idHorario', $schId);
            if(isset($request->form["idTarifaDelivery"])){
                $currSch->update([
                    'inicio' => $init,
                    'final' => $fin,
                    'descHorario' => $request->form['descHorario'],
                    'cod' => $request->form["cod"],
                    'idTarifaDelivery' => $request->form["idTarifaDelivery"]
                ]);
            }else{
                $currSch->update([
                    'inicio' => $init,
                    'final' => $fin,
                   // 'descHorario' => $schDesc
                ]);
            }


            return response()->json([
                'error' => 0,
                'message' => 'Horario actualizado correctamente'
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array([
                'context' => $ex->getTrace(),
                'User' => Auth::user()->nomUsuario
            ]));

            return response()->json([
                'error' => 1,
                'message' => 'Ha ocurrido un error al actualizar el horario.'
            ]);
        }
    }
}