<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;

class Poliza extends Model
{
    protected $table = 'poliza';
    protected $fillable = ['nombre', 'direccion', 'telefono', 'email', 'cliente_id'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
