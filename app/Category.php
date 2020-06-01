<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'clsCategoriasDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idCategoria';

    protected $fillable = ['descCategoria', 'isActivo', 'fechaAlta'];


    public function delivery(){
        return $this->belongsTo('App\Delivery', 'idTipoVehiculo', 'idTipoVehiculo');
    }

}
