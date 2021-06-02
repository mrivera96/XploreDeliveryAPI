<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderFactValues extends Model
{
    protected $table = 'tblValoresFactEnvios';
    public $timestamps = false;

    public function envio(){
        return $this->hasOne('App\DetalleDelivery','idDetalle','idDetalle');
    }
}
