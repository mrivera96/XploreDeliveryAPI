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
        'enviarNotificaciones',
        'diasGracia'
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

    public function payments()
    {
        return $this->hasMany('App\Payment', 'idCliente', 'idCliente');
    }

    public function deliveries()
    {
        return $this->hasMany('App\Delivery', 'idCliente', 'idCliente');
    }

    public function workLines()
    {
        return $this->hasMany('App\DeliveryCustomerWorkLines', 'idCliente', 'idCliente');
    }

    public function surcharges()
    {
        return $this->belongsTo('App\CustomerSurcharges', 'idCliente', 'idCliente');
    }

    public function reportRequests(){
        return $this->belongsToMany('App\ReportRequest','idCliente','idCliente');
    }
}
