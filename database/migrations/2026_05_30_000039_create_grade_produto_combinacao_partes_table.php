<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_produto_combinacao_partes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_grade_produto_combinacao');
            $table->unsignedBigInteger('id_parte_projeto');
            $table->unsignedInteger('quantidade')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_grade_produto_combinacao', 'gpcp_combinacao_fk')
                ->references('id')
                ->on('grade_produto_combinacoes');

            $table->foreign('id_parte_projeto', 'gpcp_parte_projeto_fk')
                ->references('id')
                ->on('projetos_impressao_partes');

            $table->index('id_grade_produto_combinacao', 'gpcp_combinacao_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_produto_combinacao_partes');
    }
};
