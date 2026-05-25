<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
            'FINALIZACAO_CARRETEL',
            'ESTORNO_FINALIZACAO_CARRETEL',
            'CANCELAMENTO_COMPRA'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE movimentacoes_estoque MODIFY COLUMN tipo_movimentacao ENUM(
            'ENTRADA_COMPRA',
            'SAIDA_VENDA',
            'CONSUMO_TESTE',
            'ERRO_IMPRESSAO',
            'DESCARTE',
            'AJUSTE',
            'FINALIZACAO_CARRETEL',
            'ESTORNO_FINALIZACAO_CARRETEL'
        ) NOT NULL");
    }
};
