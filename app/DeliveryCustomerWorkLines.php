<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryCustomerWorkLines extends Model
{
    protected $table = 'tblRubrosClienteDelivery';
    public $timestamps = false;
    protected $fillable = [
        'idRubro',
        'idCliente'
    ];

    public function workLine(){
        return $this->hasOne('App\DeliveryWorkLine', 'idRubro', 'idRubro');
    }

}
