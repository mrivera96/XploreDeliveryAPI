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
}
