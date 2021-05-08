<?php

namespace App\Http\Controllers;

use App\User;

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
