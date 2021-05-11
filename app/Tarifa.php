<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'clsTarifasDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idTarifaDelivery';

    protected $fillable = ['idCategoria', 'entregasMinimas', 'entregasMaximas', 'precio'];
    protected $casts = [
        'precio' => 'float:2',
        'entregasMinimas' => 'int',
        'entregasMaximas' => 'int',
    ];

    public function category(){
        return $this->belongsTo('App\Category', 'idCategoria', 'idCategoria');
    }

    public function contrato(){
        return $this->belongsTo('App\ContratoDelivery', 'idTarifaDelivery', 'idTarifaDelivery');
    }

    public function customer(){
        return $this->belongsTo('App\DeliveryClient', 'idCliente', 'idCliente');
    }

    public function rateType(){
        return $this->hasOne('App\RateType','idTipoTarifa','idTipoTarifa');
    }

    public function consolidatedDetail(){
        return $this->hasOne('App\ConsolidatedRateDetail','idTarifaDelivery','idTarifaDelivery');
    }

    public function schedules(){
        return $this->hasMany('App\Schedule','idTarifaDelivery','idTarifaDelivery');
    }

    public function rateDetail(){
        return $this->hasMany('App\RateCustomer', 'idTarifaDelivery', 'idTarifaDelivery');
    }

    public function itemDetail(){
        return $this->hasOne('App\ItemDetail', 'idTarifaDelivery', 'idTarifaDelivery');
    }

}
