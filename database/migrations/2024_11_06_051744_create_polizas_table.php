<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('polizas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_seguro');
            $table->decimal('prima_neta', 10, 2);
            $table->string('asegurado');
            $table->string('aseguradora');
            $table->date('vigencia_de');
            $table->date('vigencia_hasta');
            $table->string('periodicidad_pago');
            $table->string('archivo_pdf')->nullable();
            $table->foreignId('clients_id')->constrained('clients')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polizas');
    }
};
