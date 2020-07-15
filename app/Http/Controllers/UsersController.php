<?php

namespace App\Http\Controllers;

use App\DetalleDelivery;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public static function existeUsuario($nickName)
    {
        $existeUsuario = User::where('nickUsuario', $nickName)->count();

        return $existeUsuario;
    }

    public static function usuarioActivo($nickName)
    {
        $activo = User::where('nickUsuario', $nickName)->where('isActivo', 1)->get();

        return $activo->count();
    }

    public function listDrivers()
    {
        try {
            $drivers = User::with(['agency'])->where('isActivo', 1)->where('idPerfil', 7)->get();

            foreach ($drivers as $driver) {
                $driver->agency->city;
                $driverOrdersPending = DetalleDelivery::where('idEstado', 41)->where('idConductor', $driver->idUsuario)->count();
                $driverOrdersTransit = DetalleDelivery::where('idEstado', 43)->where('idConductor', $driver->idUsuario)->count();

                $driverLLogin = Carbon::parse($driver->lastLogin)->format('Y-m-d');
                if ($driver->lastLogin != null && $driverLLogin == Carbon::today()->format('Y-m-d')) {
                    if ($driverOrdersPending > 0 || $driverOrdersTransit > 0) {
                        $driver->state = 'Ocupado / Entregas: '.$driverOrdersTransit.' en tránsito,  '.' + '.$driverOrdersPending.' pendiente(s)';
                    } else {
                        $driver->state = 'Disponible';
                    }
                }else{
                    $driver->state = 'No disponible hoy';
                }
            }
            return response()->json(['error' => 0, 'data' => $drivers], 200);

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->nomUsuario,'context' => $ex->getTrace()));
            return response()->json(['error' => 1, 'message' => $ex->getMessage()], 500);
        }
    }

    public function createDriver(Request $request)
    {
        $request->validate(['form' => 'required']);
        try {
            $rDriver = $request->form;
            if (self::existeUsuario($rDriver['nickUsuario']) == 0) {
                $nDriver = new User();
                $nDriver->nomUsuario = $rDriver['nomUsuario'];
                $nDriver->nickUsuario = $rDriver['nickUsuario'];
                $nDriver->idAgencia = $rDriver['idAgencia'];
                $nDriver->passcodeUsuario = $rDriver['passcodeUsuario'];
                $nDriver->numCelular = $rDriver['numCelular'];
                $nDriver->idPerfil = 7;
                $nDriver->fechaCreacion = Carbon::now();
                $nDriver->isActivo = 1;
                $nDriver->save();
                return response()->json(['error' => 0,
                    'message' => 'Conductor agregado correctamente'],
                    200);
            } else {
                return response()->json(['error' => 1,
                    'message' => 'Ese nombre de usuario ya está en uso.'],
                    500);
            }

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()));
            return response()->json(['error' => 1, 'message' => $ex->getMessage()], 500);
        }
    }

    public function updateDriver(Request $request)
    {
        $request->validate(['form' => 'required']);
        try {
            $idDriver = $request->form['idUsuario'];
            $rDriver = $request->form;

            $currentDriver = User::find($idDriver);

            if ($rDriver['nickUsuario'] == $currentDriver->nickUsuario) {
                $currentDriver->update([
                    'nomUsuario' => $rDriver['nomUsuario'],
                    'numCelular' => $rDriver['numCelular'],
                    'idAgencia' => $rDriver['idAgencia'],
                    'passcodeUsuario' => $rDriver['passcodeUsuario']
                ]);

                return response()->json(['error' => 0,
                    'message' => 'Conductor actualizado correctamente'],
                    200);

            } else {
                if (self::existeUsuario($rDriver['nickUsuario']) == 0) {
                    $currentDriver->update([
                        'nomUsuario' => $rDriver['nomUsuario'],
                        'numCelular' => $rDriver['numCelular'],
                        'nickUsuario' => $rDriver['nickUsuario'],
                        'idAgencia' => $rDriver['idAgencia'],
                        'passcodeUsuario' => $rDriver['passcodeUsuario']
                    ]);
                    return response()->json(['error' => 0,
                        'message' => 'Conductor actualizado correctamente'],
                        200);
                } else {
                    return response()->json(['error' => 1,
                        'message' => 'Ese nombre de usuario ya está en uso.'],
                        500);
                }
            }

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(['error' => 1, 'message' => $ex->getMessage()], 500);
        }
    }
}
