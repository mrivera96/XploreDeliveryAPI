<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecargoDelivery extends Model
{
    protected $table = 'clsRecargosDelivery';
    protected $primaryKey = 'idRecargo';
    public $timestamps = false;
    protected $fillable = ['kilomMinimo', 'kilomMaximo', 'monto', 'idCliente'];

    public function customer(){
        return $this->belongsTo('App\DeliveryClient', 'idCliente', 'idCliente');
    }
}
