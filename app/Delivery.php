<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $table = 'tblDeliveries';
    protected $primaryKey = 'idDelivery';
    public $timestamps = false;
    protected $fillable = [
        'idDelivery',
        'nomCliente',
        'numIdentificacion',
        'numCelular',
        'fecha',
        'dirRecogida',
        'email',
        'idCategoria'
    ];


    public function detalle()
    {
        return $this->hasOne('App\DetalleDelivery', 'idDelivery', 'idDelivery');
    }

    public function category(){
        return $this->hasOne('App\Category', 'idTipoVehiculo', 'idCategoria');
    }
}
