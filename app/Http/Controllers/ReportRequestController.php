<?php

namespace App\Http\Controllers;

use App\ReportRequest;
use Exception;
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
}
