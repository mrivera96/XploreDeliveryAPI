<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $table = 'clsTiposServicios';
    protected $primaryKey = 'idTipoServicio';
    public $timestamps = false;
}
