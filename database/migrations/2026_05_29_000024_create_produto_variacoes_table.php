<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_variacoes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_produto_base');
            $table->unsignedBigInteger('id_cor');
            $table->unsignedBigInteger('id_parte_base');
            $table->string('sku', 160);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_produto_base')->references('id')->on('produtos_base');
            $table->foreign('id_cor')->references('id')->on('cores');
            $table->foreign('id_parte_base')->references('id')->on('partes_base');

            $table->unique('sku', 'produto_variacoes_sku_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_variacoes');
    }
};
