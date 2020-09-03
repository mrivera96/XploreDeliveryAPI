<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExtraChargesOrders extends Model
{
    protected $table = 'tblCargosExtraEnvios';
    protected $fillable = ['idDetalle', 'idCargoExtra','idDetalleOpcion'];
    public $timestamps = false;

    public function order(){
        return $this->belongsTo('App\DetalleDelivery','idDetalle','idDetalle');
    }

    public function extracharge(){
        return $this->belongsTo('App\ExtraCharge','idDetalle','idDetalle');
    }

    public function option(){
        return $this->belongsTo('App\DetalleOpcionesCargosExtras','idDetalleOpcion','idDetalleOpcion');
    }
}
