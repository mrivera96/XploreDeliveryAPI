<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'clsTarifasDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idTarifaDelivery';

    protected $fillable = ['idCategoria', 'entregasMinimas', 'entregasMaximas', 'precio'];

    public function category(){
        return $this->hasOne('App\Category', 'idCategoria', 'idCategoria');
    }

    public function contrato(){
        return $this->belongsTo('App\ContratoDelivery', 'idTarifaDelivery', 'idTarifaDelivery');
    }
}
