<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleOpcionesCargosExtras extends Model
{
    protected $table = 'tblDetalleOpcionesCargosExtras';
    protected $primaryKey = 'idDetalleOpcion';
    public $timestamps = false;
    protected $fillable = ['idCargoExtra', 'descripcion', 'costo', 'tiempo'];

    public function extraCharge(){
        return $this->belongsTo('App\ExtraCharge', 'idCargoExtra','idCargoExtra');
    }

    public function orders(){
        return $this->belongsTo('App\DetalleDelivery', 'idDetalleOpcion', 'idDetalleOpcion');
    }

    public function itemDetail(){
        return $this->belongsTo('App\ITemDetail', 'idDetalleOpcion', 'idDetalleOpcion');
    }
}
