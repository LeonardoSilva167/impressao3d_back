<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_composicao_itens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_produto_composicao_variacao');
            $table->unsignedBigInteger('id_item_projeto');
            $table->unsignedBigInteger('id_filamento');

            $table->decimal('peso_total', 10, 2);
            $table->string('tempo_impressao', 5);
            $table->decimal('preco_medio_grama', 15, 4);
            $table->decimal('custo_item', 15, 4);

            $table->timestamps();

            $table->foreign('id_produto_composicao_variacao', 'pci_variacao_fk')
                ->references('id')
                ->on('produto_composicao_variacoes');

            $table->foreign('id_item_projeto', 'pci_item_projeto_fk')
                ->references('id')
                ->on('projetos_impressao_parte_itens');

            $table->foreign('id_filamento', 'pci_filamento_fk')
                ->references('id')
                ->on('filamentos');

            $table->unique(
                ['id_produto_composicao_variacao', 'id_item_projeto'],
                'pci_variacao_item_unico'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_composicao_itens');
    }
};
