<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('projetos_impressao', 'custo_estimado_projeto')) {
            Schema::table('projetos_impressao', function (Blueprint $table) {
                $table->decimal('custo_estimado_projeto', 15, 4)
                    ->default(0)
                    ->after('peso_total_gramas');
            });
        }

        if (!Schema::hasColumn('projetos_impressao_cores', 'id_filamento')) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->unsignedBigInteger('id_filamento')
                    ->nullable()
                    ->after('id_cor');
            });
        }

        $this->backfillFilamentosExistentes();

        DB::statement('ALTER TABLE projetos_impressao_cores MODIFY id_filamento BIGINT UNSIGNED NOT NULL');

        $foreignExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'projetos_impressao_cores'
              AND CONSTRAINT_NAME = 'projetos_impressao_cores_id_filamento_foreign'
        ");

        if (!$foreignExists) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->foreign('id_filamento')
                    ->references('id')
                    ->on('filamentos')
                    ->restrictOnDelete();
            });
        }

        $this->recalcularCustosProjetosExistentes();
    }

    public function down(): void
    {
        if (Schema::hasColumn('projetos_impressao_cores', 'id_filamento')) {
            Schema::table('projetos_impressao_cores', function (Blueprint $table) {
                $table->dropForeign(['id_filamento']);
                $table->dropColumn('id_filamento');
            });
        }

        if (Schema::hasColumn('projetos_impressao', 'custo_estimado_projeto')) {
            Schema::table('projetos_impressao', function (Blueprint $table) {
                $table->dropColumn('custo_estimado_projeto');
            });
        }
    }

    private function backfillFilamentosExistentes(): void
    {
        $coresSemFilamento = DB::table('projetos_impressao_cores')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('id_filamento')
                    ->orWhere('id_filamento', 0);
            })
            ->get(['id', 'id_cor']);

        foreach ($coresSemFilamento as $corProjeto) {
            $filamento = DB::table('filamentos')
                ->where('id_cor', $corProjeto->id_cor)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->value('id');

            if (!$filamento) {
                throw new RuntimeException(
                    "Não foi possível migrar a cor do projeto #{$corProjeto->id}: nenhum filamento cadastrado para id_cor {$corProjeto->id_cor}."
                );
            }

            DB::table('projetos_impressao_cores')
                ->where('id', $corProjeto->id)
                ->update(['id_filamento' => $filamento]);
        }
    }

    private function recalcularCustosProjetosExistentes(): void
    {
        $projetos = DB::table('projetos_impressao')
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($projetos as $idProjeto) {
            $custo = DB::table('projetos_impressao_cores as pic')
                ->join('filamentos as fil', 'fil.id', '=', 'pic.id_filamento')
                ->where('pic.id_projeto_impressao', $idProjeto)
                ->whereNull('pic.deleted_at')
                ->whereNull('fil.deleted_at')
                ->selectRaw('ROUND(SUM(pic.peso_gramas * COALESCE(fil.preco_medio_grama, 0)), 4) as custo')
                ->value('custo');

            DB::table('projetos_impressao')
                ->where('id', $idProjeto)
                ->update(['custo_estimado_projeto' => $custo ?? 0]);
        }
    }
};
