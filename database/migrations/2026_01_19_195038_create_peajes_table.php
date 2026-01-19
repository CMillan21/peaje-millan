<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('peajes', function (Blueprint $table) {
        $table->id();
        // Campos del XML
        $table->string('numero_tag')->nullable();
        $table->integer('concesion')->nullable();
        $table->integer('tipo_tag')->nullable();
        $table->string('iut')->index(); // Es bueno indexar identificadores únicos
        $table->integer('categoria')->nullable();
        $table->integer('categoria_cobrada')->nullable();
        $table->integer('categoria_detectada')->nullable();
        $table->integer('status')->nullable();
        $table->string('hora_peaje')->nullable();  // Lo dejamos string para guardar formato "00:57:35"
        $table->string('fecha_peaje')->nullable(); // Lo dejamos string para guardar formato "17-10-2024" tal cual viene
        $table->integer('importe_peaje')->nullable();
        $table->integer('numero_reenvio')->nullable();
        // Datos de la ruta anidada
        $table->string('entrada')->nullable();
        $table->string('salida')->nullable();
        $table->integer('sentido')->nullable();
        
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peajes');
    }
};
