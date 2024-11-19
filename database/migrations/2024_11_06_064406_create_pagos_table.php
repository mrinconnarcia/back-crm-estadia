<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagosTable extends Migration
{
    public function up()
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->foreignId('poliza_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pendiente');
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('pagos');
    }
}
