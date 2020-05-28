<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'clsTipoVehiculo';
    public $timestamps = false;
    protected $primaryKey = 'idTipoVehiculo';

    protected $fillable = ['descTipoVehiculo', 'isActivo', 'delivery'];


    public function delivery(){
        return $this->belongsTo('App\Delivery', 'idTipoVehiculo', 'idTipoVehiculo');
    }
}
