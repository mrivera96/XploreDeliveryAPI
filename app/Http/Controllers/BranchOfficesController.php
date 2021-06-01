<?php

namespace App\Http\Controllers;

use App\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BranchOfficesController extends Controller
{
    public function getCustomerBranchOffices(Request $request)
    {
        try {
            if ($request->idCustomer == null) {
                $currCustomer = Auth::user()->idCliente;
            } else {
                $currCustomer = $request->idCustomer;
            }
            $myBranchOffices = Branch::with('cliente')
                ->where([
                    'isActivo' => 1,
                    'idCliente' => $currCustomer
                ])
                ->get();

            return response()->json([
                'error' => 0,
                'data' => $myBranchOffices
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->nomUsuario, 'context' => $ex->getTrace()));
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al obtener las direcciones.'//$ex->getMessage()
            ]);
        }
    }

    public function newBranch(Request $request)
    {
        try {
            $rBranch = $request->form;
            $nBranch = new Branch();

            $nBranch->nomSucursal = $rBranch['nomSucursal'];
            if ($rBranch['numTelefono']) {
                $nBranch->numTelefono = $rBranch['numTelefono'];
            }
            $nBranch->idCliente = Auth::user()->idCliente;
            $nBranch->direccion = $rBranch['direccion'];
            $nBranch->fechaAlta = Carbon::now();
            $nBranch->isActivo = 1;
            if ($rBranch['instrucciones'] != '') {
                $nBranch->instrucciones = $rBranch['instrucciones'];
            }

            if ($rBranch['isDefault'] == true) {
                if (Branch::where('idCliente', Auth::user()->idCliente)->count() > 0) {
                    Branch::where('idCliente', Auth::user()->idCliente)
                        ->update(['isDefault' => false]);
                }

                $nBranch->isDefault = true;
            }
            $nBranch->save();

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Dirección agregada correctamente.'
                ], 200);

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()));
            return response()
                ->json([
                    'error' => 1,
                    'message' => 'Ocurrió un error al agregar la dirección'//$ex->getMessage()
                ], 500);
        }
    }

    public function updateBranch(Request $request)
    {
        try {
            $bId = $request->form['idSucursal'];
            $form = $request->form;

            $currBranch = Branch::where('idSucursal', $bId);
            $currBranch->update([
                'nomSucursal' => $form['nomSucursal'],
                'numTelefono' => $form['numTelefono'],
                'direccion' => $form['direccion']
            ]);

            $currBranch->update(['instrucciones' => $form['instrucciones']]);

            if ($form['isDefault'] == true) {
                if (Branch::where('idCliente', Auth::user()->idCliente)->count() > 0) {
                    Branch::where('idCliente', Auth::user()->idCliente)
                        ->update(['isDefault' => false]);
                }

                $currBranch->update(['isDefault' => true]);
            }

            return response()->json([
                'error' => 0,
                'message' => 'Dirección actualizada correctamente'
            ]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), ['context' => $exception->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function deleteBranch(Request $request)
    {
        try {
            $bId = $request->id;

            Branch::find($bId)->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Dirección eliminada correctamente'
            ]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), ['context' => $exception->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ]);
        }
    }
}
