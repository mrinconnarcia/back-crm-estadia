<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Poliza;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $fillable = ['nombre', 'telefono', 'direccion', 'email'];

    public function polizas()
    {
        return $this->hasMany(Poliza::class);
    }
}
