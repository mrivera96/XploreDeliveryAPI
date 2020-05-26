<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'tblSucursalesClientesDelivery';
    public $timestamps = false;
    protected $primaryKey = 'idSucursal';
}
