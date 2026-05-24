<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras_itens', function (Blueprint $table) {
            $table->decimal('qtd_original', 15, 4)->nullable()->after('qtd_interna');
            $table->decimal('qtd_atual', 15, 4)->nullable()->after('qtd_original');
        });

        DB::table('compras_itens')
            ->whereNull('deleted_at')
            ->update([
                'qtd_original' => DB::raw('qtd_interna'),
                'qtd_atual'    => DB::raw('qtd_interna'),
            ]);

        DB::statement('ALTER TABLE compras_itens MODIFY qtd_original DECIMAL(15, 4) NOT NULL');
        DB::statement('ALTER TABLE compras_itens MODIFY qtd_atual DECIMAL(15, 4) NOT NULL');

        DB::statement('ALTER TABLE itens CHANGE estoque estoque_atual DECIMAL(15, 4) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE itens CHANGE custo_medio preco_medio_atual DECIMAL(15, 4) NOT NULL DEFAULT 0');

        $itemIds = DB::table('compras_itens')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('id_item');

        foreach ($itemIds as $idItem) {
            $lotes = DB::table('compras_itens')
                ->whereNull('deleted_at')
                ->where('id_item', $idItem)
                ->select('qtd_atual', 'valor_unitario_real')
                ->get();

            $estoqueAtual    = 0;
            $somaQtdComCusto = 0;
            $somaCusto       = 0;

            foreach ($lotes as $lote) {
                $qtdAtual = (float) $lote->qtd_atual;
                $estoqueAtual += $qtdAtual;

                if ($qtdAtual > 0) {
                    $somaQtdComCusto += $qtdAtual;
                    $somaCusto += $qtdAtual * (float) $lote->valor_unitario_real;
                }
            }

            DB::table('itens')
                ->where('id', $idItem)
                ->update([
                    'estoque_atual'     => round($estoqueAtual, 4),
                    'preco_medio_atual' => $somaQtdComCusto > 0
                        ? round($somaCusto / $somaQtdComCusto, 4)
                        : 0,
                ]);
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE itens CHANGE estoque_atual estoque DECIMAL(15, 4) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE itens CHANGE preco_medio_atual custo_medio DECIMAL(15, 4) NOT NULL DEFAULT 0');

        Schema::table('compras_itens', function (Blueprint $table) {
            $table->dropColumn(['qtd_original', 'qtd_atual']);
        });
    }
};
