<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BillingData extends Model
{
    protected $table = 'Delivery.FacturacionDelivery';

    public function delivery(){
        return $this->hasOne('App\Delivery','idDelivery','idDelivery');
    }

    public function detalle(){
        return $this->hasOne('App\Detallelivery','idDetalle','idDetalle');
    }
}
