<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;


    protected $table = 'tblUsuarios';
    protected $primaryKey = 'idUsuario';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nomUsuario', 'nickUsuario', 'passUsuario', 'isActivo', 'lastLogin', 'fechaCreacion', 'idAgencia',
        'passcodeUsuario', 'idCliente','numCelular'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'passUsuario',
    ];

    public function getAuthPassword()
    {
        return $this->passUsuario;
    }

    public function details(){
        return $this->belongsToMany('App\DetalleDelivery', 'idUsuario', 'idConductor');
    }

    public function cliente(){
        return $this->belongsTo('App\DeliveryClient', 'idCliente', 'idCliente');
    }

    public function agency(){
        return $this->belongsTo('App\Agency', 'idAgencia', 'idAgencia');
    }

    public function payments(){
        return $this->hasMany('App\Payment', 'idUsuario', 'idUsuario');
    }

}
