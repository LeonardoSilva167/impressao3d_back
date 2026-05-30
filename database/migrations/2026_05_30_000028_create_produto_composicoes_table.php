<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_composicoes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_produto');
            $table->unsignedBigInteger('id_projeto_impressao');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_produto', 'pc_produto_fk')
                ->references('id')
                ->on('produtos_base');

            $table->foreign('id_projeto_impressao', 'pc_projeto_impressao_fk')
                ->references('id')
                ->on('projetos_impressao');

            $table->index('id_produto', 'pc_produto_idx');
            $table->index('id_projeto_impressao', 'pc_projeto_impressao_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_composicoes');
    }
};
