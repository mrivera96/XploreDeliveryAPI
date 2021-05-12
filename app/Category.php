<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'clsCategoriasDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idCategoria';

    protected $fillable = ['descCategoria', 'isActivo', 'fechaAlta'];

    protected $casts = [
        'idTipoServicio' => 'int',
        'orden' => 'int',
    ];


    public function delivery()
    {
        return $this->belongsTo('App\Delivery', 'idTipoVehiculo', 'idTipoVehiculo');
    }

    public function categoryExtraCharges()
    {
        return $this->hasMany('App\ExtraChargeCategory', 'idCategoria', 'idCategoria');
    }

    public function rate(){
        return $this->hasMany('App\Tarifa', 'idCategoria', 'idCategoria');
    }

    public function surcharges(){
        return $this->hasMany('App\RecargoDelivery', 'idCategoria', 'idCategoria');
    }

    public function serviceType(){
        return $this->hasOne('App\ServiceType', 'idTipoServicio', 'idTipoServicio');
    }

}
