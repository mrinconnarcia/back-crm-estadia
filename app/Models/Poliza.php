<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poliza extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_seguro',
        'prima_neta',
        'asegurado',
        'aseguradora',
        'vigencia_de',
        'vigencia_hasta',
        'periodicidad_pago',
        'archivo_pdf',
        'clients_id'
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'clients_id');
    }
}
