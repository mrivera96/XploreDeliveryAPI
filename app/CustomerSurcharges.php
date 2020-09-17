<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerSurcharges extends Model
{
    protected $table = 'tblRecargosClienteDelivery';
    public $timestamps = false;

    public function customer(){
        return $this->hasOne('App\DeliveryClient', 'idCliente', 'idCliente');
    }

    public function surcharge(){
        return $this->hasOne('App\RecargoDelivery', 'idRecargo', 'idRecargo');
    }
}
