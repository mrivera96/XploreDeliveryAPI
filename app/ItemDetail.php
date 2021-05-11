<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemDetail extends Model
{
    protected $table = 'tblValoresFactDelivery';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'idTarifaDelivery',
        'idCargoExtra',
        'idDetalleOpcion',
        'idRecargo',
        'tYK',
        'cobVehiculo',
        'servChofer',
        'recCombustible',
        'cobTransporte',
        'isv',
        'tasaTuris'];

    protected $casts = [
        'tYK' => 'float',
        'cobVehiculo' => 'float',
        'servChofer' => 'float',
        'recCombustible' => 'float',
        'cobTransporte' => 'float',
        'isv' => 'float',
        'tasaTuris' => 'float'
    ];

    public function rate(){
        return $this->belongsTo('App\Tarifa','idTarifaDelivery','idTarifaDelivery');
    }


}
