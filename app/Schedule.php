<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $table = 'clsHorariosDelivery';
    protected $primaryKey = 'idHorario';
    public $timestamps = false;
    protected $fillable = ['descHorario','dia','inicio','final','fechaRegistro','idTarifaDelivery'];

    public function rate(){
        return $this->belongsTo('App\Tarifa','idTarifaDelivery','idTarifaDelivery');
    }
}
