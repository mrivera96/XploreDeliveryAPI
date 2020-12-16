<?php

namespace App\Http\Controllers;

use App\DetalleDelivery;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
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

    
}
