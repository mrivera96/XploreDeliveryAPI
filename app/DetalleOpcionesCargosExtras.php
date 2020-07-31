<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleOpcionesCargosExtras extends Model
{
    protected $table = 'tblDetalleOpcionesCargosExtras';
    protected $primaryKey = 'idDetalleOpcion';
    public $timestamps = false;
    protected $fillable = ['idCargoExtra', 'descripcion', 'costo'];

    public function extraCharge(){
        return $this->belongsTo('App\ExtraCharge', 'idCargoExtra','idCargoExtra');
    }
}
