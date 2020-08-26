<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleDelivery extends Model
{
    protected $table = 'tblDetalleDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idDetalle';
    protected $fillable = [
        'idDelivery',
        'nFactura',
        'nomDestinatario',
        'numCel',
        'cargosExtra',
        'cTotal',
        'direccion',
        'tiempo'
    ];

    public function delivery(){
        return $this->belongsTo('App\Delivery', 'idDelivery', 'idDelivery');
    }

    public function conductor(){
        return $this->hasOne('App\User', 'idUsuario', 'idConductor');
    }

    public function estado(){
        return $this->hasOne('App\Estado', 'idEstado', 'idEstado');
    }

    public function ExtraCharge(){
        return $this->hasOne('App\ExtraCharge', 'idCargoExtra', 'idCargoExtra');
    }

    public function ExtraChargeOption(){
        return $this->hasOne('App\DetalleOpcionesCargosExtras', 'idDetalleOpcion', 'idDetalleOpcion');
    }

    public function photography(){
        return $this->hasMany('App\FotografiaDetalle', 'idDetalle', 'idDetalle');
    }
}
