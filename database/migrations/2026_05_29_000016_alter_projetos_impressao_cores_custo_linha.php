<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexProjetoExists = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'projetos_impressao_cores'
              AND index_name = 'projetos_impressao_cores_id_projeto_impressao_index'
        ");

        if (!$indexProjetoExists) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->index('id_projeto_impressao', 'projetos_impressao_cores_id_projeto_impressao_index');
            });
        }

        $uniqueExists = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'projetos_impressao_cores'
              AND index_name = 'projetos_impressao_cores_projeto_cor_unique'
        ");

        if ($uniqueExists) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->dropUnique('projetos_impressao_cores_projeto_cor_unique');
            });
        }

        if (!Schema::hasColumn('projetos_impressao_cores', 'preco_medio_grama')) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->decimal('preco_medio_grama', 15, 4)
                    ->default(0)
                    ->after('peso_gramas');
            });
        }

        if (!Schema::hasColumn('projetos_impressao_cores', 'custo_estimado')) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->decimal('custo_estimado', 15, 4)
                    ->default(0)
                    ->after('preco_medio_grama');
            });
        }

        $this->backfillCustosLinhas();
    }

    public function down(): void
    {
        if (Schema::hasColumn('projetos_impressao_cores', 'custo_estimado')) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->dropColumn('custo_estimado');
            });
        }

        if (Schema::hasColumn('projetos_impressao_cores', 'preco_medio_grama')) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->dropColumn('preco_medio_grama');
            });
        }

        Schema::table('projetos_impressao_cores', function (Blueprint $table) {
            $table->unique(['id_projeto_impressao', 'id_cor'], 'projetos_impressao_cores_projeto_cor_unique');
        });
    }

    private function backfillCustosLinhas(): void
    {
        $linhas = DB::table('projetos_impressao_cores as pic')
            ->join('filamentos as fil', 'fil.id', '=', 'pic.id_filamento')
            ->whereNull('pic.deleted_at')
            ->whereNull('fil.deleted_at')
            ->select('pic.id', 'pic.peso_gramas', 'fil.preco_medio_grama')
            ->get();

        foreach ($linhas as $linha) {
            $preco = (float) ($linha->preco_medio_grama ?? 0);
            $custo = round((float) $linha->peso_gramas * $preco, 4);

            DB::table('projetos_impressao_cores')
                ->where('id', $linha->id)
                ->update([
                    'preco_medio_grama' => $preco,
                    'custo_estimado'    => $custo,
                ]);
        }
    }
};
