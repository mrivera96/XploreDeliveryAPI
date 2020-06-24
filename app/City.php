<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
   protected $table = 'tblCiudades';
   protected $primaryKey = 'idCiudad';

   public function agencias(){
       return $this->hasMany('App\Agency', 'idCiudad','idCiudad');
   }
}
