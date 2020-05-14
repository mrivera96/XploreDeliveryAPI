<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    protected $table = 'clsEstados';
    protected $primaryKey = 'idEstado';
    public $timestamps = false;
}
