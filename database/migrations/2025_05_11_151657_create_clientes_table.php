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
        Schema::create('orgaos', function (Blueprint $table) {
            $table->id();
            $table->string('orgao_nome');
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::create('unidade_compradoras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orgaos_id')->nullable();            
            $table->string('codigo', 6)->unique();
            $table->string('nome');            
            $table->string('uf', 2);            
            $table->string('cidade');            
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('orgaos_id')->references('id')->on('orgaos');
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();     
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orgaos');
        Schema::dropIfExists('unidade_compradoras');
        Schema::dropIfExists('clientes');
    }
};
