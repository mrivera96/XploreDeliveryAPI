<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryWorkLine extends Model
{
    protected $table = 'clsRubrosDelivery';
    public $timestamps = false;
    protected $fillable = [
        'nomRubro',
        'descRubro',
        'isActivo',
        'fechaRegistro'
    ];

    public function deliveryCustomerWorkLines(){
        return $this->belongsTo('App\DeliveryCustomerWorkLines','idRubro','idRubro');
    }
}
