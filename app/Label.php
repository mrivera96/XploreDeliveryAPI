<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $table = 'tblEtiquetasDelivery';
    public $timestamps = false;
    protected $fillable = ['descEtiqueta','idCliente','fechaRegistro'];
}
