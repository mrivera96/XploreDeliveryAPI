<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryClient extends Model
{

    protected $table = 'tblClientesDelivery';
    protected $primaryKey = 'idCliente';
    public $timestamps = false;

    public function cliente(){
        return $this->hasMany('App\DeliveryUser', 'idCliente', 'idCliente');
    }

    public function direcciones(){
        return $this->hasMany('App\Branch', 'idCliente', 'idCliente');
    }
}
