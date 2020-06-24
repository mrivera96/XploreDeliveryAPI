<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    protected $table = 'clsAgencias';
    protected $primaryKey = 'idAgencia';
    public $timestamps = false;

    public function drivers(){
        return $this->hasMany('App\User','idAgencia','idAgencia');
    }

    public function city(){
        return $this->belongsTo('App\City','idCiudad','idCiudad');
    }
}
