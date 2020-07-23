<?php

namespace App\Http\Controllers;

use App\DeliveryClient;
use App\User;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeliveryUsersController extends Controller
{
    public function list()
    {
        try {
            $customers = DeliveryClient::with(['payments','payments.paymentType','deliveries.detalle'])->get();

            return response()
                ->json([
                    'error' => 0,
                    'data' => $customers
                ], 200);
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), array('User' => Auth::user()->nomUsuario, 'context' => $exception->getTrace()));
            return response()
                ->json([
                    'error' => 1,
                    'message' => $exception->getMessage()
                ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $changePassForm = $request->form;
        try {

            $currUser = User::where('idUsuario', Auth::user()->idUsuario)->get()->first();

            if (Auth::user()->passUsuario == utf8_encode($this->obtenerCifrado($changePassForm['oldPass'])) || Hash::check($changePassForm['oldPass'], Auth::user()->passUsuario)) {
                $newPass = Hash::make($changePassForm['newPass']);
                $currUser->passUsuario = $newPass;
                $currUser->save();

                return response()->json([
                    'error' => 0,
                    'message' => 'Contraseña actualizada correctamente.'
                ], 200);
            } else {

                return response()->json([
                    'error' => 1,
                    'message' => 'La contraseña actual ingresada no coincide con nuestros registros.'
                ], 500);
            }


        } catch (Exception $exception) {
            Log::error($exception->getMessage(),
                array('User' => Auth::user()->nomUsuario, 'context' => $exception->getTrace())
            );
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ], 500);
        }

    }

    public function newCustomer(Request $request)
    {
        try {
            $rCustomer = $request->form;

            if (UsersController::existeUsuario($rCustomer['email']) == 0) {
                $nCustomer = new DeliveryClient();
                $nCustomer->nomEmpresa = $rCustomer['nomEmpresa'];
                $nCustomer->nomRepresentante = $rCustomer['nomRepresentante'];
                $nCustomer->numIdentificacion = $rCustomer['numIdentificacion'];
                $nCustomer->numTelefono = $rCustomer['numTelefono'];
                $nCustomer->email = $rCustomer['email'];
                $nCustomer->isActivo = 1;
                $nCustomer->fechaAlta = Carbon::now();
                $nCustomer->save();

                $nUser = new User();
                $nUser->idPerfil = 8;
                $nUser->nomUsuario = $rCustomer['nomRepresentante'];
                $nUser->nickUsuario = $rCustomer['email'];
                $nUser->passUsuario = Hash::make($rCustomer['numIdentificacion']);
                $nUser->isActivo = 1;
                $nUser->idCliente = $nCustomer->idCliente;
                $nUser->fechaCreacion = Carbon::now();
                $nUser->save();

                $receivers = $nCustomer->email;

                $this->sendmail($receivers, $nCustomer);
                return response()->json([
                    'error' => 0,
                    'message' => 'Cliente agregado correctamente.'
                ], 200);


            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'Ya existe este usuario.'
                ], 500);
            }

        } catch (Exception $exception) {
            Log::error($exception->getMessage(), array('User' => Auth::user()->nomUsuario,
                    'context' => $exception->getTrace())
            );
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function updateCustomer(Request $request)
    {
        try {
            $rCustomer = $request->form;
            $currCustomer = DeliveryClient::find($rCustomer['idCliente']);
            $currUser = User::where('idCliente', $rCustomer['idCliente']);

            if ($rCustomer['email'] == $currCustomer->email) {
                $currCustomer->update([
                    'nomEmpresa' => $rCustomer['nomEmpresa'],
                    'nomRepresentante' => $rCustomer['nomRepresentante'],
                    'numIdentificacion' => $rCustomer['numIdentificacion'],
                    'numTelefono' => $rCustomer['numTelefono'],
                ]);

                $currUser->update([
                    'nomUsuario' => $rCustomer['nomRepresentante'],
                ]);

                return response()->json([
                    'error' => 0,
                    'message' => 'Cliente actualizado correctamente.'
                ], 200);

            } else {
                if (UsersController::existeUsuario($rCustomer['email']) == 0) {
                    $currCustomer->update([
                        'nomEmpresa' => $rCustomer['nomEmpresa'],
                        'nomRepresentante' => $rCustomer['nomRepresentante'],
                        'numIdentificacion' => $rCustomer['numIdentificacion'],
                        'numTelefono' => $rCustomer['numTelefono'],
                        'email' => $rCustomer['email'],
                    ]);

                    $currUser->update([
                        'nomUsuario' => $rCustomer['nomRepresentante'],
                        'nickUsuario' => $rCustomer['email'],
                    ]);

                    return response()->json([
                        'error' => 0,
                        'message' => 'Cliente actualizado correctamente.'
                    ], 200);

                } else {
                    return response()->json([
                        'error' => 1,
                        'message' => 'Ya existe este usuario.'
                    ], 500);
                }
            }

        } catch (Exception $exception) {
            Log::error($exception->getMessage(), array('User' => Auth::user()->nomUsuario,
                'context' => $exception->getTrace()));
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function sendmail($mail, $cliente)
    {
        $data["email"] = $mail;
        $data["client_name"] = $cliente->Representante;
        $data["subject"] = 'Detalles de Acceso Xplore Delivery';
        $data["cliente"] = $cliente;
        $data["from"] = 'Xplore Delivery';

        try {
            Mail::send('userCredentials', $data, function ($message) use ($data) {
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

    private function obtenerCifrado($psswd)
    {
        $httpClient = new Client();
        $res = $httpClient->get('https://appconductores.xplorerentacar.com/mod.ajax/encriptar.php?password=' . $psswd);
        return json_decode($res->getBody());
    }

    public function testAccessDetails(Request $request)
    {
        $cliente = DeliveryClient::where('idCliente', $request->idCliente)->get()->first();
        return view('mails.userCredentials', compact('cliente'));

    }


}
