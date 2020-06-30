<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $table = 'clsHorariosDelivery';
    protected $primaryKey = 'idHorario';
    public $timestamps = false;
    protected $fillable = ['dia','inicio','final','fechaRegistro'];
}
