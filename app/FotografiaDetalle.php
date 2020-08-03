<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FotografiaDetalle extends Model
{
    protected $table = 'tblFotografiasDelivery';
    public $timestamps = false;

    public function detalle(){
        return $this->belongsTo('App\DetalleDelivery', 'idDetalle', 'idDetalle');
    }
}
