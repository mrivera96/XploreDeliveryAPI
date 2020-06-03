<?php

namespace App\Http\Controllers;

use App\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchOfficesController extends Controller
{
    public function getCustomerBranchOffices(){
        try {
            $myBranchOffices = Branch::where('isActivo', 1)->where('idCliente', Auth::user()->idCliente)->get();
            foreach ($myBranchOffices as $bOffice){
                $bOffice->cliente;
            }
            return response()->json([
                'error' => 0,
                'data' => $myBranchOffices
            ], 200);
        }catch (\Exception $ex){
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function newBranch(Request $request){
        try{
            $rBranch = $request->form;
            $nBranch = new Branch();

            $nBranch->nomSucursal = $rBranch['nomSucursal'];
            if($rBranch['numTelefono']){
                $nBranch->numTelefono = $rBranch['numTelefono'];
            }
            $nBranch->idCliente = Auth::user()->idCliente;
            $nBranch->direccion = $rBranch['direccion'];
            $nBranch->fechaAlta = Carbon::now();
            $nBranch->isActivo = 1;
            $nBranch->save();

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Dirección agregada correctamente.'
                ],200);

        }catch (\Exception $ex){
            return response()
                ->json([
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],500);
        }
    }

    public function updateBranch(Request $request){
        try{
            $bId = $request->form['idSucursal'];
            $form = $request->form;

            $currBranch = Branch::where('idSucursal', $bId);
            $currBranch->update([
                'nomSucursal' => $form['nomSucursal'],
                'numTelefono' => $form['numTelefono'],
                'direccion' => $form['direccion']
            ]);

            return response()->json([
                'error' => 0,
                'message' => 'Dirección actualizada correctamente'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function deleteBranch(Request $request){
        try{
            $bId = $request->id;

            Branch::find($bId)->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Dirección eliminada correctamente'
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ]);
        }
    }
}
