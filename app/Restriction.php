<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Restriction extends Model
{
    protected $table = 'clsRestriccionesDelivery';
    protected $primaryKey = 'idRestriccion';
    public $timestamps = false;
    protected $fillable = [
        'idRestriccion',
        'descripcion',
        'valMinimo',
        'valMaximo',
        'isActivo',
        'fechaRegistro',
    ];
}
