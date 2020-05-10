<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContratoDelivery extends Model
{
    protected $table = 'tblContratosDelivery';
    protected $primaryKey = 'idContratoDelivery';
    public $timestamps = false;

    public function delivery(){
        return $this->hasOne('App\Delivery', 'idDelivery', 'idDelivery');
    }

    public function tarifaDelivery(){
        return $this->hasOne('App\Tarifa', 'idTarifaDelivery', 'idTarifaDelivery');
    }

    public function recargoDelivery(){
        return $this->hasOne('App\RecargoDelivery', 'idRecargoDelivery', 'idRecargoDelivery');
    }

    public function usuarioContrato(){
        return $this->hasOne('App\User', 'idUsuario', 'idUsuario');
    }

    public function vehiculoContrato(){
        return $this->hasOne('App\Vehiculo', 'idVehiculo', 'idVehiculo');
    }
}
