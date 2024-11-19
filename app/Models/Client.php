<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'apellidos', 'telefono', 'contacto_emergencia', 'correo', 'fecha_nacimiento', 'user_id'
    ];

    protected $appends = ['edad'];

    // Accessor para calcular la edad
    public function getEdadAttribute()
    {
        return Carbon::parse($this->fecha_nacimiento)->age;
    }

    // Si quieres formatear la fecha de nacimiento
    protected $casts = [
        'fecha_nacimiento' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
