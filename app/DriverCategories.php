<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverCategories extends Model
{
    protected $table = 'tblCategoriasConductoresDelivery';
    public $timestamps = false;

    public function driver(){
        return $this->hasOne('App\User','idUsuario','idConductor');
    }

    public function category(){
        return $this->hasOne('App\Category','idCategoria','idCategoria');
    }
}
