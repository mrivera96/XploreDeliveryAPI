<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RateType extends Model
{
    protected $table = 'clsTiposTarifasDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idTipoTarifa';

    public function rates(){
        return $this->belongsToMany('App\Tarifa','idTipoTarifa','idTipoTarifa');
    }
}
