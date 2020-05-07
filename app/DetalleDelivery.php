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
}
