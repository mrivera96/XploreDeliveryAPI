<?php

namespace App\Http\Controllers;

use App\User;
use Exception;
use GuzzleHttp\Client;
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
                $cripPass = $this->obtenerCifrado($password);

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

    public function testGettingCript(Request $request){
        return response()->json($this->obtenerCifrado($request->myPass)) ;
    }


    private function obtenerCifrado($psswd){
        $httpClient = new Client();
        $res = $httpClient->get('https://appconductores.xplorerentacar.com/mod.ajax/encriptar.php?password='.$psswd);
        return json_decode($res->getBody());
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
