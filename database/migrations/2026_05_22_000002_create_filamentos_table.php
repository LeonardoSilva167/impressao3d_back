<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filamentos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_tipo_material');
            $table->unsignedBigInteger('id_cor');
            $table->unsignedBigInteger('id_linha_marca');
            $table->unsignedBigInteger('id_marca');

            $table->string('codigo', 20);
            $table->string('resumo', 255);
            $table->decimal('qtd', 15, 2)->nullable()->default(0);
            $table->decimal('preco_medio_grama', 15, 4)->nullable()->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_tipo_material')->references('id')->on('tipos_materiais');
            $table->foreign('id_cor')->references('id')->on('cores');
            $table->foreign('id_linha_marca')->references('id')->on('linhas_marcas');
            $table->foreign('id_marca')->references('id')->on('marcas');

            $table->unique(
                ['id_tipo_material', 'id_cor', 'id_linha_marca', 'id_marca'],
                'filamentos_combinacao_unica'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filamentos');
    }
};
