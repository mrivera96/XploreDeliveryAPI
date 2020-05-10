<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'tblVehiculos';
    protected $primaryKey = 'idVehiculo';
    public $timestamps = false;

    public function contrato(){
        return $this->belongsTo('App\ContratoDelivery', 'idVehiculo', 'idVehiculo');
    }

}
