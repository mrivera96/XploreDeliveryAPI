<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RateCustomer extends Model
{
    protected $table = 'tblDetalleTarifasDelivery';
    protected $fillable = ['idTarifaDelivery', 'idCliente'];
    public $timestamps = false;

    public function customer(){
        return $this->hasOne('App\DeliveryClient', 'idCliente','idCliente');
    }

    public function rate(){
        return $this->hasOne('App\Tarifa', 'idTarifaDelivery', 'idTarifaDelivery');
    }
}
