<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public static function existeUsuario($nickName)
    {

        $existeUsuario = DB::select('exec Seg_ExisteUsuario ?', array($nickName));

        return $existeUsuario[0]->Registros;
    }

    public static function usuarioActivo($nickName)
    {
        $activo = User::where('nickUsuario', $nickName)->where('isActivo', 1)->get();

        return $activo->count();
    }

    public function listDrivers()
    {
        try {
            $users = User::where('isActivo',1)->where('idPerfil', 7)->get();
            return response()->json(['error'=>0, 'data'=>$users],200);

        }catch (\Exception $ex){
            return response()->json(['error'=>1, 'message'=>$ex->getMessage()],500);
        }
    }
}
