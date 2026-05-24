<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_item');
            $table->enum('tipo_movimentacao', [
                'ENTRADA_COMPRA',
                'SAIDA_VENDA',
                'CONSUMO_TESTE',
                'ERRO_IMPRESSAO',
                'DESCARTE',
                'AJUSTE',
            ]);
            $table->decimal('qtd', 15, 4);
            $table->text('observacao')->nullable();
            $table->dateTime('data_movimentacao');
            $table->timestamps();

            $table->foreign('id_item')->references('id')->on('itens');
            $table->index(['id_item', 'data_movimentacao']);
            $table->index('tipo_movimentacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
    }
};
