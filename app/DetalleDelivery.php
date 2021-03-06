<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleDelivery extends Model
{
    protected $table = 'tblDetalleDelivery';
    public $timestamps = false;
    protected $fillable = [
        'idDelivery',
        'nFactura',
        'nomDestinatario',
        'numCel',
        'direccion'
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
}
