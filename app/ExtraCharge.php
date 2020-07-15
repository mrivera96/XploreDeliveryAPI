<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExtraCharge extends Model
{
    protected $table = 'tblCargosExtrasDetalleEnvio';
    protected $primaryKey = 'idCargoExtra';
    public $timestamps = false;
    protected $fillable = ['nombre', 'costo'];
}
