<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'monto', 'fecha_pago', 'poliza_id', 'status',
    ];

    public function poliza()
    {
        return $this->belongsTo(Poliza::class);
    }
}
