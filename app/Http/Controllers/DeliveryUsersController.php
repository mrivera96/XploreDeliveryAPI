<?php

namespace App\Http\Controllers;

use App\DeliveryClient;
use App\DeliveryUser;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class DeliveryUsersController extends Controller
{
    public function list()
    {
        try {
            $customers = DeliveryClient::all();
            return response()
                ->json([
                    'error' => 0,
                    'data' => $customers
                ], 200);
        }catch (Exception $exception){
            return response()
                ->json([
                    'error' => 1,
                    'message' => $exception->getMessage()
                ], 500);
        }
    }

    public function changePassword(Request $request){
        $changePassForm = $request->form;
        try {

            $currUser = User::where('idUsuario', Auth::user()->idUsuario)->get()->first();

            if(Auth::user()->passUsuario == utf8_encode($this->encriptar($changePassForm['oldPass'])) || Hash::check($changePassForm['oldPass'], Auth::user()->passUsuario)){
                $newPass =  Hash::make($changePassForm['newPass']);
                $currUser->passUsuario = $newPass;
                $currUser->save();

                return response()->json([
                    'error' => 0,
                    'message' => 'Contraseña actualizada correctamente.'
                ],200);
            }else{
                return response()->json([
                    'error' => 1,
                    'message' => 'La contraseña actual ingresada no coincide con nuestros registros.'
                ],500);
            }



        }catch (Exception $exception){
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ],500);
        }

    }

    public function newCustomer(Request $request){
        try {
            $rCustomer = $request->form;

            if(UsersController::existeUsuario($rCustomer['email']) == 0){
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
                ],200);
            }else{
                return response()->json([
                    'error' => 1,
                    'message' => 'ya existe este usuario.'
                ],500);
            }

        }catch (Exception $exception){
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
            ],500);
        }

    }

    public function sendmail($mail, $cliente)
    {
        $data["email"] = $mail;
        $data["client_name"] = $cliente->Representante;
        $data["subject"] = 'Detalles de Acceso Xplore Delivery';
        $data["cliente"] = $cliente;

        try {
            Mail::send('mails.userCredentials', $data, function ($message) use ($data) {
                $message->to($data["email"], $data["client_name"])
                    ->subject($data["subject"]);
            });
        } catch (Exception $exception) {
            $this->serverstatuscode = "0";
            $this->serverstatusdes = $exception->getMessage();
        }
        if (Mail::failures()) {
            return false;
        } else {
            return true;
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

        Do {
            $IL_SUMA = $IL_SUMA + ord(substr($vl_cadena_conv, $IL_COUNT, 1));
            $IL_COUNT = $IL_COUNT + 1;

        } While ($IL_COUNT <= $IL_LONGI);

        $IL_BASE = intval($IL_SUMA / $IL_LONGI);
        $IL_COUNT = 0;

        Do {
            $pwd = $pwd . Chr(ord(substr($vl_cadena_conv, $IL_COUNT, 1)) + $IL_BASE);
            $IL_COUNT = $IL_COUNT + 1;
        } While ($IL_COUNT < $IL_LONGI);


        $pwd = Chr($IL_BASE - 15) . $pwd . Chr(2 * $IL_BASE);

        return $pwd;
    }

    public function testAccessDetails(Request $request)
    {
        $cliente = DeliveryClient::where('idCliente', $request->idCliente)->get()->first();
        return view('mails.userCredentials', compact('cliente'));

    }

    public function testEncryption(Request $request){
        $myPass = Hash::make($request->myPasss);
        return response($myPass) ;
    }
    public function testDecryption(Request $request){
        $hashed = Hash::make('uXplore2020%');
        if(Hash::check($request->myPass,$hashed)){
            return response(1);
        }else{
            return response(0);
        }

    }
/*
    public function encriptarTTT($iString)
    {
        $pwd = '';

        $IL_LONGI = (int)(strlen($iString) / 2);

        $vl_cadena_conv = substr($iString, -$IL_LONGI) . $iString . substr($iString, 0, $IL_LONGI);

        $IL_LONGI = strlen($vl_cadena_conv);

        $IL_COUNT = 0;
        $IL_SUMA = 0;

        Do {
            $IL_SUMA = $IL_SUMA + ord(substr($vl_cadena_conv, $IL_COUNT, 1));
            $IL_COUNT = $IL_COUNT + 1;

        } While ($IL_COUNT < $IL_LONGI);


        $IL_BASE = (int)(intval($IL_SUMA / $IL_LONGI) );
        $IL_COUNT = 0;


        Do {
            $pwd = $pwd . Chr(ord(substr($vl_cadena_conv, $IL_COUNT, 1)) + $IL_BASE);
            $IL_COUNT = $IL_COUNT + 1;
        } While ($IL_COUNT < $IL_LONGI);


        $pwd = Chr($IL_BASE - 15) . $pwd . Chr(2 * $IL_BASE);

        return $pwd;
    }*/
    public function desencriptar($iString)
    {

        $pwd = '';
        $li_base = (int)(ord(substr($iString, -1)) / 2);
        $vl_cadena_conv = substr($iString, 1, (strlen($iString) - 3));

        $li_longi = (int)((strlen(utf8_decode($vl_cadena_conv)) / 4));
        $vl_cadena_conv = mb_substr($vl_cadena_conv,  $li_longi  ,  strlen(utf8_decode($vl_cadena_conv)) - (2 * $li_longi) );
        $li_longi = strlen($vl_cadena_conv);

        $li_count = 0;

        do{
            $pwd = $pwd . Chr(ord(substr($vl_cadena_conv, $li_count , 1)) - $li_base);
            $li_count = $li_count + 1;
        }while($li_count < $li_longi);

        return $pwd;
    }
}
