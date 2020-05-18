<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CtrlEstadoDelivery extends Model
{
    protected $table = 'tblCtrlEstadosDelivery';
    public $timestamps = false;
    protected $fillable = ['idDelivery', 'idEstado', 'idUsuario', 'fechaRegistro'];
}
