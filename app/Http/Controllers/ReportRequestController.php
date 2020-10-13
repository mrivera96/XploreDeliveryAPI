<?php

namespace App\Http\Controllers;

use App\ReportRequest;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportRequestController extends Controller
{
    public function getReportRequests()
    {
        try {
            $reportRequests = ReportRequest::with(['customer'])->get();
            return response()->json([
                'error' => 0,
                'data' => $reportRequests
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al cargar los datos'//$ex->getMessage()
            ], 500);
        }
    }

    public function createReportRequest(Request $request)
    {
        $request->validate([
            'idCliente' => 'required',
            'correo' => 'required'
        ]);

        $customer = $request->idCliente;
        $email = $request->correo;
        try {
            $nReportRequests = new ReportRequest();
            $nReportRequests->idCliente = $customer;
            $nReportRequests->correo = $email;
            $nReportRequests->fechaRegistro = Carbon::now();
            $nReportRequests->idUsuario = Auth::user()->idUsuario;

            return response()->json([
                'error' => 0,
                'message' => 'Registro agregado correctamente'
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al crear el registro'//$ex->getMessage()
            ], 500);
        }
    }
}
