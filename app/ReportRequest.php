<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportRequest extends Model
{
    protected $table = 'tblReportesDelivery';
    public $timestamps = false;
    protected $fillable = ['idCliente','idUsuario','fechaRegistro','correo'];

    public function customer(){
        return $this->hasOne('App\DeliveryClient','idCliente','idCliente');
    }
}
