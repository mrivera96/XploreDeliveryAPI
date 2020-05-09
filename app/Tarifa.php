<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'clsTarifasDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idTarifaDelivery';

    public function category(){
        return $this->hasOne('App\Category', 'idTipoVehiculo', 'idCategoria');
    }
}
