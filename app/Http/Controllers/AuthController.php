<?php

namespace App\Http\Controllers;

use App\DeliveryClient;
use App\Tarifa;
use App\User;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        /*if (Carbon::now()->hour >= 22 || Carbon::now()->hour < 5) {
            return response()->json([
                'error' => 1,
                'message' => 'Lo sentimos, en estos momentos nuestra plataforma se encuentra en mantenimiento.',
                'status' => 401
            ], 401);
        }*/

        if (UsersController::existeUsuario($nickname) != 0) {
            if (UsersController::usuarioActivo($nickname) > 0) {
                $cripPass = $this->obtenerCifrado($password);

                $auth = User::whereIn('idPerfil', [1, 8, 9])->where('nickUsuario', $nickname)->get();

                if ($auth->where('passUsuario', $cripPass)->count() > 0 || Hash::check($password, User::whereIn('idPerfil', [1, 8, 9])->where('nickUsuario', $nickname)->get()->first()->getAuthPassword())) {

                    Auth::login($auth->first());
                    $user = Auth::user();
                    $tkn = $user->createToken('XploreDeliverypApi')->accessToken;
                    $user->access_token = $tkn;
                    $user->cliente;
                    $custConsolidatedRates = Tarifa::where('idTipoTarifa', 2)
                        ->whereHas('rateDetail', function ($q) use ($user) {
                            $q->where('idCliente', $user->idCliente);
                        })->count();
                    $custForConsolidatedRates = Tarifa::where('idTipoTarifa', 4)
                        ->whereHas('rateDetail', function ($q) use ($user) {
                            $q->where('idCliente', $user->idCliente);
                        })->count();

                    $hasConsolidatedRate = false;
                    if ($custConsolidatedRates > 0) {
                        $hasConsolidatedRate = true;
                    }

                    $hasFConsolidatedRate = false;
                    if ($custForConsolidatedRates > 0) {
                        $hasFConsolidatedRate = true;
                    }

                    $user->permiteConsolidada = $hasConsolidatedRate;
                    $user->permiteConsolidadaForanea = $hasFConsolidatedRate;

                    return response()->json(
                        [
                            'error' => 0,
                            'user' => $user,
                            'status' => 200
                        ],
                        200
                    );
                } else {
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

    public function numberLogin(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'password' => 'required|string',
        ]);

        $number = $request->number;
        $password = $request->password;
        $user =  User::whereHas('cliente', function($q) use ($number){
            $q->where('numTelefono',$number);
        })->first();
        $nickname = $user->nickUsuario;

        /*if (Carbon::now()->hour >= 22) {
            return response()->json([
                'error' => 1,
                'message' => 'Lo sentimos, en estos momentos nuestra plataforma se encuentra en mantenimiento.',
                'status' => 401
            ], 401);
        }*/


        if (UsersController::usuarioActivo($nickname) > 0) {
            $cripPass = $this->obtenerCifrado($password);

            $auth = User::whereIn('idPerfil', [1, 8, 9])->where('nickUsuario', $nickname)->get();

            if ($auth->where('passUsuario', $cripPass)->count() > 0 || Hash::check($password, User::whereIn('idPerfil', [1, 8, 9])->where('nickUsuario', $nickname)->get()->first()->getAuthPassword())) {

                Auth::login($auth->first());
                $user = Auth::user();
                $tkn = $user->createToken('XploreDeliverypApi')->accessToken;
                $user->access_token = $tkn;
                $user->cliente;
                $custConsolidatedRates = Tarifa::where('idTipoTarifa', 2)
                    ->whereHas('rateDetail', function ($q) use ($user) {
                        $q->where('idCliente', $user->idCliente);
                    })->count();
                $custForConsolidatedRates = Tarifa::where('idTipoTarifa', 4)
                    ->whereHas('rateDetail', function ($q) use ($user) {
                        $q->where('idCliente', $user->idCliente);
                    })->count();

                $hasConsolidatedRate = false;
                if ($custConsolidatedRates > 0) {
                    $hasConsolidatedRate = true;
                }

                $hasFConsolidatedRate = false;
                if ($custForConsolidatedRates > 0) {
                    $hasFConsolidatedRate = true;
                }

                $user->permiteConsolidada = $hasConsolidatedRate;
                $user->permiteConsolidadaForanea = $hasFConsolidatedRate;

                return response()->json(
                    [
                        'error' => 0,
                        'user' => $user,
                        'status' => 200
                    ],
                    200
                );
            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'Las credenciales que ha ingresado no son correctas.',
                    'status' => 401
                ], 401);
            }
        } else {
            return response()->json([
                'error' => 1,
                'message' => 'Su usuario se encuentra inactivo. Comuníquese con el departamento de IT para resolver el conflicto. '.$nickname
            ], 401);
        }
    }

    public function signUp(Request $request){
        try {
            $rCustomer = $request->form;

            if (UsersController::existeUsuario($rCustomer['email']) == 0) {
                $nCustomer = new DeliveryClient();
                $nCustomer->nomEmpresa = $rCustomer['nomRepresentante'];
                $nCustomer->nomRepresentante = $rCustomer['nomRepresentante'];
                $nCustomer->numIdentificacion = $rCustomer['numIdentificacion'];
                $nCustomer->numTelefono = $rCustomer['numTelefono'];
                $nCustomer->email = $rCustomer['email'];
                $nCustomer->enviarNotificaciones = 0;
                $nCustomer->isActivo = 1;
                $nCustomer->montoGracia = 100;
                $nCustomer->fechaAlta = Carbon::now();
                $nCustomer->save();

                $nUser = new User();
                $nUser->idPerfil = 8;
                $nUser->nomUsuario = $rCustomer['nomRepresentante'];
                $nUser->nickUsuario = $rCustomer['email'];
                $nUser->passUsuario = Hash::make($rCustomer['newPass']);
                $nUser->isActivo = 1;
                $nUser->idCliente = $nCustomer->idCliente;
                $nUser->fechaCreacion = Carbon::now();
                $nUser->save();

                $receivers = $nCustomer->email;

                $this->welcomeMail($receivers, $nCustomer);
                return response()->json([
                    'error' => 0,
                    'message' => 'Registro completado correctamente. ¡Bienvenido(a)!'
                ], 200);
            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'El email que ingresaste está en uso.'
                ], 500);
            }
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $exception->getTrace()
            ));
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al agregar el cliente' //$exception->getMessage()
            ], 500);
        }
    }

    public function testGettingCript(Request $request)
    {
        return response()->json($this->obtenerCifrado($request->myPass));
    }


    private function obtenerCifrado($psswd)
    {
        $httpClient = new Client();
        $res = $httpClient->get('https://appconductores.xplorerentacar.com/mod.ajax/encriptar.php?password=' . $psswd);
        return json_decode($res->getBody());
    }


    public function logout(Request $request)
    {
        try {

            $request->user()->token()->revoke();

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Successfully logged out'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function passwordRecovery(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.email' => 'required',
            'form.numIdentificacion' => 'required'
        ]);

        try {
            $remail = $request->form['email'];
            $rNumId = $request->form['numIdentificacion'];

            if (UsersController::existeUsuario($remail) != 0) {
                if (UsersController::usuarioActivo($remail) > 0) {
                    $correct = DeliveryClient::where('email', $remail)->where('numIdentificacion', $rNumId);
                    if ($correct->count() > 0) {
                        $newPass = Hash::make($rNumId);
                        User::where('nickUsuario', $remail)
                            ->update([
                                'passUsuario' => $newPass
                            ]);

                        $receivers = $remail;

                        $this->sendmail($receivers, $correct->get()->first());

                        return response()->json([
                            'error' => 0,
                            'message' => 'Recuperación de contraseña realizada correctamente. Recibirás un e-mail con tus detalles de acceso'
                        ], 200);
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
                    'message' => 'El email ingresado no se encuentra en nuestros registros.',
                    'status' => 401
                ], 401);
            }
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                'context' => $ex->getTrace()
            ));

            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error en la recuperación de tu contraseña, por favor intenta nuevamente.'
            ]);
        }
    }

    public function sendmail($mail, $cliente)
    {
        $data["email"] = $mail;
        $data["client_name"] = $cliente->Representante;
        $data["subject"] = 'Recuperación de contraseña - Xplore Delivery';
        $data["cliente"] = $cliente;
        $data["from"] = 'Xplore Delivery';

        try {
            Mail::send('passwordRecoveryNotification', $data, function ($message) use ($data) {
                $message
                    ->from('noreply@xplorerentacar.com', $data["from"])
                    ->to($data["email"], $data["client_name"])
                    ->subject($data["subject"]);
            });
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['context' => $exception->getTrace()]);
            $this->serverstatuscode = "0";
            $this->serverstatusdes = $exception->getMessage();
        }
    }

    public function welcomeMail($mail, $cliente)
    {
        $data["email"] = $mail;
        $data["client_name"] = $cliente->Representante;
        $data["subject"] = 'Xplore Delivery - ¡Bienvenido(a)!';
        $data["cliente"] = $cliente;
        $data["from"] = 'Xplore Delivery';

        try {
            Mail::send('welcomeMail', $data, function ($message) use ($data) {
                $message
                    ->from('noreply@xplorerentacar.com', $data["from"])
                    ->to($data["email"], $data["client_name"])
                    ->subject($data["subject"]);
            });
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['context' => $exception->getTrace()]);
            $this->serverstatuscode = "0";
            $this->serverstatusdes = $exception->getMessage();
        }
    }

    public function testPassword(Request $request)
    {
        $isPass = Hash::check($request->pass, User::where('idCliente', 103)->get('passUsuario')->first());
        return response()->json($isPass);
    }


    public function generatePassword(Request $request)
    {
        $pass = \Illuminate\Support\Facades\Hash::make($request->pass);
        return response()->json($pass);
    }

    public function verifyMail(Request $request)
    {
        $request->validate(['mail' => 'required']);
        $email = $request->mail;
        $ok = false;
        if (UsersController::existeUsuario($email) != 0 && UsersController::usuarioActivo($email) > 0) {
            $ok = true;
        }

        return response()->json([
            'error' => 0,
            'data' => $ok
        ]);
    }

    public function verifyNumber(Request $request)
    {
        $request->validate(['number' => 'required']);
        $number = $request->number;
        $ok = false;
        if (DeliveryClient::where('numTelefono', $number)->count() > 0) {
            $ok = true;
        }

        return response()->json([
            'error' => 0,
            'data' => $ok
        ]);
    }


}
