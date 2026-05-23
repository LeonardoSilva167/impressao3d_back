<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_categoria_item');
            $table->string('descricao', 255);
            $table->string('codigo', 50);
            $table->string('unidade_medida', 20);
            $table->boolean('controla_estoque')->default(true);
            $table->boolean('gera_custo')->default(true);
            $table->boolean('ativo')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_categoria_item')->references('id')->on('categorias_itens');
            $table->unique('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens');
    }
};
