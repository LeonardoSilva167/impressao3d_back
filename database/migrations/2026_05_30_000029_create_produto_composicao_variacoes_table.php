<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_composicao_variacoes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_produto_composicao');
            $table->unsignedBigInteger('id_produto_variacao');

            $table->decimal('custo_total_filamentos', 15, 4)->default(0);
            $table->string('tempo_total_impressao', 5)->default('00:00');

            $table->timestamps();

            $table->foreign('id_produto_composicao', 'pcv_composicao_fk')
                ->references('id')
                ->on('produto_composicoes');

            $table->foreign('id_produto_variacao', 'pcv_variacao_fk')
                ->references('id')
                ->on('produto_variacoes');

            $table->unique(
                ['id_produto_composicao', 'id_produto_variacao'],
                'pcv_composicao_variacao_unico'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_composicao_variacoes');
    }
};
