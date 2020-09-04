<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExtraChargesOrders extends Model
{
    protected $table = 'tblCargosExtraEnvios';

    protected  $primaryKey = 'id';
    protected $fillable = ['idDetalle', 'idCargoExtra','idDetalleOpcion'];
    public $timestamps = false;

    public function order(){
        return $this->belongsTo('App\DetalleDelivery','idDetalle','idDetalle');
    }

    public function extracharge(){
        return $this->hasOne('App\ExtraCharge','idCargoExtra','idCargoExtra');
    }

    public function option(){
        return $this->hasOne('App\DetalleOpcionesCargosExtras','idDetalleOpcion','idDetalleOpcion');
    }
}
