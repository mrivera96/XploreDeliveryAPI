<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExtraChargeCategory extends Model
{
    protected $table = 'tblDetalleCargosExtras';
    public $timestamps = false;
    protected $fillable = [
        'idCargoExtra',
        'idCategoria'
    ];

    public function extraCharge(){
        return $this->belongsTo('App\ExtraCharge', 'idCargoExtra','idCargoExtra');
    }

    public function category(){
        return $this->belongsTo('App\Category', 'idCategoria','idCategoria');
    }
}
