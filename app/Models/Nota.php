<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $fillable = ['clients_id', 'contenido'];

    // Relación con el cliente
    public function cliente()
    {
        return $this->belongsTo(Client::class);
    }
}
