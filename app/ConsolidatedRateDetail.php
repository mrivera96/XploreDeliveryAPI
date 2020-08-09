<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConsolidatedRateDetail extends Model
{
    protected $table = 'tblDetalleTarifaConsolidada';
    protected $fillable = [
        'idTarifaDelivery',
        'radioMaximo',
        'dirRecogida'
    ];
    public $timestamps = false;

    public function rate(){
        return $this->belongsTo('App\Tarifa', 'idTarifaDelivery', 'idTarifaDelivery');
    }
}
