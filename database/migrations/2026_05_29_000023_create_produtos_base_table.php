<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos_base', function (Blueprint $table) {
            $table->id();

            $table->string('descricao_produto', 120);
            $table->string('codigo_base', 20);
            $table->string('sku_base', 120);

            $table->unsignedBigInteger('id_categoria');
            $table->unsignedBigInteger('id_modelo');
            $table->unsignedBigInteger('id_linha');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_categoria')->references('id')->on('categorias_produtos');
            $table->foreign('id_modelo')->references('id')->on('modelos_produtos');
            $table->foreign('id_linha')->references('id')->on('linhas_produtos');

            $table->unique('sku_base', 'produtos_base_sku_base_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos_base');
    }
};
