<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $colunasCusto = function (Blueprint $table): void {
            $table->decimal('custo_filamento', 15, 4)->default(0);
            $table->decimal('custo_energia', 15, 4)->default(0);
            $table->decimal('custo_desgaste', 15, 4)->default(0);
            $table->decimal('custo_total', 15, 4)->default(0);
        };

        Schema::table('projetos_impressao', function (Blueprint $table) use ($colunasCusto) {
            $colunasCusto($table);
        });

        Schema::table('projetos_impressao_partes', function (Blueprint $table) use ($colunasCusto) {
            $colunasCusto($table);
        });

        Schema::table('projetos_impressao_parte_itens', function (Blueprint $table) use ($colunasCusto) {
            $colunasCusto($table);
        });

        Schema::table('produto_variacao_filamentos', function (Blueprint $table) {
            $table->decimal('custo_filamento', 15, 4)->default(0)->after('peso_item');
            $table->decimal('custo_energia', 15, 4)->default(0)->after('custo_filamento');
            $table->decimal('custo_desgaste', 15, 4)->default(0)->after('custo_energia');
            $table->decimal('custo_total', 15, 4)->default(0)->after('custo_desgaste');
        });

        DB::table('produto_variacao_filamentos')
            ->whereNull('deleted_at')
            ->update([
                'custo_filamento' => DB::raw('custo_item'),
                'custo_total'     => DB::raw('custo_item'),
            ]);

        Schema::table('produto_variacao_filamentos', function (Blueprint $table) {
            $table->dropColumn('custo_item');
        });

        Schema::table('grade_produto_itens', function (Blueprint $table) {
            $table->decimal('custo_filamento', 15, 4)->default(0)->after('tempo_total');
            $table->decimal('custo_energia', 15, 4)->default(0)->after('custo_filamento');
            $table->decimal('custo_desgaste', 15, 4)->default(0)->after('custo_energia');
        });

        DB::table('grade_produto_itens')
            ->whereNull('deleted_at')
            ->update([
                'custo_filamento' => DB::raw('custo_total'),
            ]);

        $this->recalcularCustosItensExistentes();
    }

    private function recalcularCustosItensExistentes(): void
    {
        $config = DB::table('configuracoes')
            ->whereNull('deleted_at')
            ->first(['custo_energia_kwh', 'custo_desgaste_hora']);

        if (!$config) {
            return;
        }

        $custoEnergiaKwh   = (float) $config->custo_energia_kwh;
        $custoDesgasteHora = (float) $config->custo_desgaste_hora;

        $itens = DB::table('projetos_impressao_parte_itens')
            ->whereNull('deleted_at')
            ->get([
                'id',
                'id_projeto_impressao_parte',
                'tempo_impressao',
                'peso_parte',
                'peso_suporte',
                'peso_corado',
                'peso_torre',
            ]);

        $custosPorParte = [];

        foreach ($itens as $item) {
            $pesoTotal = round(
                (float) $item->peso_parte
                + (float) $item->peso_suporte
                + (float) $item->peso_corado
                + (float) $item->peso_torre,
                2
            );

            if (!preg_match('/^\d{1,2}:\d{2}$/', (string) $item->tempo_impressao)) {
                continue;
            }

            [$horas, $minutos] = array_map('intval', explode(':', (string) $item->tempo_impressao));
            $horasDecimais = round($horas + ($minutos / 60), 4);

            $custoFilamento = 0.0;
            $custoEnergia   = round($horasDecimais * $custoEnergiaKwh, 4);
            $custoDesgaste  = round($horasDecimais * $custoDesgasteHora, 4);
            $custoTotal     = round($custoFilamento + $custoEnergia + $custoDesgaste, 4);

            DB::table('projetos_impressao_parte_itens')
                ->where('id', $item->id)
                ->update([
                    'custo_filamento' => $custoFilamento,
                    'custo_energia'   => $custoEnergia,
                    'custo_desgaste'  => $custoDesgaste,
                    'custo_total'     => $custoTotal,
                    'updated_at'      => now(),
                ]);

            $idParte = (int) $item->id_projeto_impressao_parte;

            if (!isset($custosPorParte[$idParte])) {
                $custosPorParte[$idParte] = [
                    'custo_filamento' => 0.0,
                    'custo_energia'   => 0.0,
                    'custo_desgaste'  => 0.0,
                    'custo_total'     => 0.0,
                ];
            }

            $custosPorParte[$idParte]['custo_filamento'] += $custoFilamento;
            $custosPorParte[$idParte]['custo_energia']   += $custoEnergia;
            $custosPorParte[$idParte]['custo_desgaste']  += $custoDesgaste;
            $custosPorParte[$idParte]['custo_total']     += $custoTotal;
        }

        $custosPorProjeto = [];

        foreach ($custosPorParte as $idParte => $custos) {
            $idProjeto = (int) DB::table('projetos_impressao_partes')
                ->where('id', $idParte)
                ->value('id_projeto_impressao');

            DB::table('projetos_impressao_partes')
                ->where('id', $idParte)
                ->update([
                    'custo_filamento' => round($custos['custo_filamento'], 4),
                    'custo_energia'   => round($custos['custo_energia'], 4),
                    'custo_desgaste'  => round($custos['custo_desgaste'], 4),
                    'custo_total'     => round($custos['custo_total'], 4),
                    'updated_at'      => now(),
                ]);

            if (!isset($custosPorProjeto[$idProjeto])) {
                $custosPorProjeto[$idProjeto] = [
                    'custo_filamento' => 0.0,
                    'custo_energia'   => 0.0,
                    'custo_desgaste'  => 0.0,
                    'custo_total'     => 0.0,
                ];
            }

            foreach (['custo_filamento', 'custo_energia', 'custo_desgaste', 'custo_total'] as $campo) {
                $custosPorProjeto[$idProjeto][$campo] += $custos[$campo];
            }
        }

        foreach ($custosPorProjeto as $idProjeto => $custos) {
            DB::table('projetos_impressao')
                ->where('id', $idProjeto)
                ->update([
                    'custo_filamento' => round($custos['custo_filamento'], 4),
                    'custo_energia'   => round($custos['custo_energia'], 4),
                    'custo_desgaste'  => round($custos['custo_desgaste'], 4),
                    'custo_total'     => round($custos['custo_total'], 4),
                    'updated_at'      => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('produto_variacao_filamentos', function (Blueprint $table) {
            $table->decimal('custo_item', 15, 4)->default(0)->after('peso_item');
        });

        DB::table('produto_variacao_filamentos')
            ->whereNull('deleted_at')
            ->update([
                'custo_item' => DB::raw('custo_filamento'),
            ]);

        Schema::table('produto_variacao_filamentos', function (Blueprint $table) {
            $table->dropColumn(['custo_filamento', 'custo_energia', 'custo_desgaste', 'custo_total']);
        });

        Schema::table('grade_produto_itens', function (Blueprint $table) {
            $table->dropColumn(['custo_filamento', 'custo_energia', 'custo_desgaste']);
        });

        foreach (['projetos_impressao_parte_itens', 'projetos_impressao_partes', 'projetos_impressao'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn(['custo_filamento', 'custo_energia', 'custo_desgaste', 'custo_total']);
            });
        }
    }
};
