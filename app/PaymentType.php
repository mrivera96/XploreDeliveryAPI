<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    protected $table = 'clsTiposPago';
    protected $primaryKey = 'idTipoPago';
    public $timestamps = false;
}
