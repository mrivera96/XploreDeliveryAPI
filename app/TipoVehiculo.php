<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoVehiculo extends Model
{
    protected $table = 'clsTipoVehiculo';
    public $timestamps = false;
    protected $primaryKey = 'idTipoVehiculo';

    protected $fillable = ['descTipoVehiculo', 'isActivo', 'delivery'];

}
