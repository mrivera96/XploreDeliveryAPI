<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'tblSucursalesClientesDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idSucursal';

    protected $fillable = ['nomSucursal', 'numTelefono', 'idCliente', 'direccion', 'fechAlta', 'isActivo'];


    public function cliente(){
        return $this->belongsTo('App\DeliveryClient', 'idCliente', 'idCliente');
    }
}
