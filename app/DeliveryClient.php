<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryClient extends Model
{

    protected $table = 'tblClientesDelivery';
    protected $primaryKey = 'idCliente';
    public $timestamps = false;

    protected $fillable = [
        'nomEmpresa',
        'nomRepresentante',
        'numIdentificacion',
        'numTelefono',
        'email',
    ];

    public function cliente()
    {
        return $this->hasMany('App\DeliveryUser', 'idCliente', 'idCliente');
    }

    public function direcciones()
    {
        return $this->hasMany('App\Branch', 'idCliente', 'idCliente');
    }

    public function rates()
    {
        return $this->hasMany('App\Tarifa', 'idCliente', 'idCliente');
    }

    public function surcharges()
    {
        return $this->hasMany('App\RecargoDelivery', 'idCliente', 'idCliente');
    }

    public function payments(){
        return $this->hasMany('App\Payment', 'idCliente', 'idCliente');
    }
}
