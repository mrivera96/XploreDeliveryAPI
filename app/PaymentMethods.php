<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMethods extends Model
{
    protected $table = 'tblFormasPagoClientesDelivery';
    public $timestamps = false;
    protected $fillable = ['tokend_card','anio','mes','cvv','fechaRegistro','idCliente'];
}
