<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExtraCharge extends Model
{
    protected $table = 'tblCargosExtrasDetalleEnvio';
    protected $primaryKey = 'idCargoExtra';
    public $timestamps = false;
    protected $fillable = ['nombre', 'costo', 'tipoCargo'];

    public function extrachargeCategories(){
        return $this->hasMany('App\ExtraChargeCategory', 'idCargoExtra','idCargoExtra');
    }

    public function options(){
        return $this->hasMany('App\DetalleOpcionesCargosExtras', 'idCargoExtra','idCargoExtra');
    }
}
