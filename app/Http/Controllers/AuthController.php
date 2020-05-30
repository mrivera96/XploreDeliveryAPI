<?php

namespace App\Http\Controllers;

use App\User;
use Exception;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'nickname' => 'required|string',
            'password' => 'required|string',
        ]);

        $nickname = $request->nickname;
        $password = $request->password;

        if (UsersController::existeUsuario($nickname) != 0) {
            if (UsersController::usuarioActivo($nickname) > 0) {
                $cripPass = utf8_encode($this->encriptar($password));

                $auth = User::where('nickUsuario', $nickname)->get();

                if ($auth->where('passUsuario', $cripPass)->count() > 0 || Hash::check($password, User::where('nickUsuario', $nickname)->get()->first()->getAuthPassword())) {

                    Auth::login($auth->first());
                    $user = Auth::user();
                    $tkn = $user->createToken('XploreDeliverypApi')->accessToken;
                    $user->access_token = $tkn;
                    $user->cliente;

                    return response()->json(
                        [
                            'error' => 0,
                            'user' => $user,
                            'status' => 200
                        ],
                        200
                    );
                }else {
                    return response()->json([
                        'error' => 1,
                        'message' => 'Las credenciales que ha ingresado no son correctas.',
                        'status' => 401
                    ], 401);
                }
            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'Su usuario se encuentra inactivo. Comuníquese con el departamento de IT para resolver el conflicto.'
                ], 401);
            }


        } else {
            return response()->json([
                'error' => 1,
                'message' => 'Autenticación no encontrada.',
                'status' => 401
            ], 401);
        }

    }

    public function encriptar($iString)
    {
        $pwd = "";

        $IL_LONGI = (int)(strlen($iString) / 2);
        $vl_cadena_conv = substr($iString, -$IL_LONGI) . $iString . substr($iString, 0, $IL_LONGI);

        $IL_LONGI = strlen($vl_cadena_conv);
        $IL_COUNT = 0;
        $IL_SUMA = 0;

        do {
            $IL_SUMA = $IL_SUMA + ord(substr($vl_cadena_conv, $IL_COUNT, 1));
            $IL_COUNT = $IL_COUNT + 1;

        } while ($IL_COUNT <= $IL_LONGI);

        $IL_BASE = intval($IL_SUMA / $IL_LONGI);
        $IL_COUNT = 0;

        do {
            $pwd = $pwd . Chr(ord(substr($vl_cadena_conv, $IL_COUNT, 1)) + $IL_BASE);
            $IL_COUNT = $IL_COUNT + 1;
        } while ($IL_COUNT < $IL_LONGI);


        $pwd = Chr($IL_BASE - 15) . $pwd . Chr(2 * $IL_BASE);

        return $pwd;
    }

    public function logout(Request $request)
    {
        try {

            $request->user()->token()->revoke();

            return response()->json([
                'error' => 0,
                'message' => 'Successfully logged out'],
                200);
        } catch (Exception $ex) {
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()],
                500);
        }

    }
}
