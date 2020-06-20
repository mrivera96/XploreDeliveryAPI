<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'tblPagosDeliveries';
    protected $primaryKey = 'idPago';
    public $timestamps = false;

    protected $fillable = [
        'fechaPago',
        'monto',
        'tipoPago',
        'idUsuario',
        'fechaRegistro',
        'idCliente',
        'referencia',
        'banco'];

    public function user(){
        return $this->belongsTo('App\User','idUsuario','idUsuario');
    }

    public function customer(){
        return $this->belongsTo('App\DeliveryClient', 'idCliente','idCliente');
    }

    public function paymentType(){
        return $this->hasOne('App\PaymentType', 'idTipoPago','tipoPago');
    }

}
