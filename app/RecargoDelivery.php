<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecargoDelivery extends Model
{
    protected $table = 'clsRecargosDelivery';
    protected $primaryKey = 'idRecargo';
    public $timestamps = false;
    protected $fillable = ['kilomMinimo', 'kilomMaximo', 'monto', 'idCliente', 'idCategoria'];

    public function customer()
    {
        return $this->belongsTo('App\DeliveryClient', 'idCliente', 'idCliente');
    }

    public function category()
    {
        return $this->belongsTo('App\Category', 'idCategoria', 'idCategoria');
    }

    public function customerSurcharges()
    {
        return $this->belongsTo('App\CustomerSurcharges', 'idRecargo', 'idRecargo');
    }
}
