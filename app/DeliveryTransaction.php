<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryTransaction extends Model
{
    protected $table = 'tblTransaccionesDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idTransaccion';
}
