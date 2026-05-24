<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE movimentacoes_estoque MODIFY COLUMN tipo_movimentacao ENUM(
            'ENTRADA_COMPRA',
            'SAIDA_VENDA',
            'CONSUMO_TESTE',
            'ERRO_IMPRESSAO',
            'DESCARTE',
            'AJUSTE',
            'FINALIZACAO_CARRETEL'
        ) NOT NULL");

        Schema::table('movimentacoes_estoque', function (Blueprint $table) {
            $table->unsignedBigInteger('id_compra_item')->nullable()->after('id_item');
            $table->unsignedSmallInteger('gramatura')->nullable()->after('qtd');
            $table->decimal('saldo_anterior', 15, 4)->nullable()->after('gramatura');
            $table->decimal('saldo_posterior', 15, 4)->nullable()->after('saldo_anterior');
            $table->unsignedBigInteger('id_carreteis_finalizados')->nullable()->after('saldo_posterior');

            $table->foreign('id_compra_item')->references('id')->on('compras_itens');
            $table->foreign('id_carreteis_finalizados')->references('id')->on('carreteis_finalizados');
            $table->index('id_compra_item');
            $table->index('id_carreteis_finalizados');
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes_estoque', function (Blueprint $table) {
            $table->dropForeign(['id_compra_item']);
            $table->dropForeign(['id_carreteis_finalizados']);
            $table->dropIndex(['id_compra_item']);
            $table->dropIndex(['id_carreteis_finalizados']);
            $table->dropColumn([
                'id_compra_item',
                'gramatura',
                'saldo_anterior',
                'saldo_posterior',
                'id_carreteis_finalizados',
            ]);
        });

        DB::statement("ALTER TABLE movimentacoes_estoque MODIFY COLUMN tipo_movimentacao ENUM(
            'ENTRADA_COMPRA',
            'SAIDA_VENDA',
            'CONSUMO_TESTE',
            'ERRO_IMPRESSAO',
            'DESCARTE',
            'AJUSTE'
        ) NOT NULL");
    }
};
