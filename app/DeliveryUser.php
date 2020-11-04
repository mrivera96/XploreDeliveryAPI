<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class DeliveryUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'tblUsuariosDelivery';
    protected $primaryKey = 'idUsuario';
    public $timestamps = false;

    protected $fillable = [
        'nomUsuario', 'nickUsuario', 'passUsuario', 'isActivo', 'idCliente'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'passUsuario', 'passcodeUsuario'
    ];

    public function getAuthPassword()
    {
        return $this->passUsuario;
    }

    public function cliente(){
        return $this->belongsTo('App\DeliveryClient', 'idCliente', 'idCliente');
    }
}
