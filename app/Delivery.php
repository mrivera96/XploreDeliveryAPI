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
        'fechaReserva',
        'dirRecogida',
        'email',
        'idCategoria',
        'idEstado',
        'tarifaBase',
        'recargos',
        'cargosExtra',
        'total',
        'distTotal',
        'isRuteo',
        'regAdmin',
        'registradoPor'
    ];


    public function detalle()
    {
        return $this->hasMany('App\DetalleDelivery', 'idDelivery', 'idDelivery');
    }

    public function category(){
        return $this->hasOne('App\Category', 'idCategoria', 'idCategoria');
    }

    public function contrato(){
        return $this->belongsTo('App\ContratoDelivery', 'idDelivery', 'idDelivery');
    }

    public function estado(){
        return $this->hasOne('App\Estado', 'idEstado', 'idEstado');
    }

    public function cliente(){
        return $this->belongsTo('App\DeliveryClient', 'idCliente', 'idCliente');
    }

    public function usuario(){
        return $this->belongsTo('App\User', 'registradoPor', 'idUsuario');
    }


}
