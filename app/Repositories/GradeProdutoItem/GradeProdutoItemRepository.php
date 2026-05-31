<?php

namespace App\Repositories\GradeProdutoItem;

use App\Models\GradeProdutoItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeProdutoItemRepository
{
    public function findById(int|string $id): ?GradeProdutoItem
    {
        return GradeProdutoItem::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function create(array $data): GradeProdutoItem
    {
        return GradeProdutoItem::create($data);
    }

    public function deleteByGradeId(int $idGrade): void
    {
        GradeProdutoItem::where('id_grade_produto', $idGrade)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (GradeProdutoItem $item) => $item->delete());
    }

    public function getPaginateQuery(): Builder
    {
        $nomePartesSql = $this->sqlNomePartesGrade('gpi.id_grade_produto');

        return DB::query()
            ->select(
                'gpi.id',
                'gpi.id_grade_produto',
                'gpi.id_grade_produto_combinacao',
                'gpi.sku',
                'gpi.nome_produto',
                'gpi.peso_total',
                'gpi.tempo_total',
                'gpi.custo_filamento',
                'gpi.custo_energia',
                'gpi.custo_desgaste',
                'gpi.custo_total',
                'gpi.status',
                'gpi.created_at',
                'pb.codigo_base',
                'gpc.descricao as descricao_combinacao',
                DB::raw("({$nomePartesSql}) as partes"),
            )
            ->from('grade_produto_itens as gpi')
            ->join('grades_produtos as gp', 'gp.id', '=', 'gpi.id_grade_produto')
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->leftJoin('grade_produto_combinacoes as gpc', function ($join) {
                $join->on('gpc.id', '=', 'gpi.id_grade_produto_combinacao')
                    ->whereNull('gpc.deleted_at');
            })
            ->whereNull('gpi.deleted_at')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->orderBy('gpi.nome_produto');
    }

    public function findByIdWithRelations(int|string $id): ?object
    {
        $nomePartesSql = $this->sqlNomePartesGrade('gpi.id_grade_produto');

        return DB::table('grade_produto_itens as gpi')
            ->select(
                'gpi.id',
                'gpi.id_grade_produto',
                'gpi.id_grade_produto_combinacao',
                'gpi.sku',
                'gpi.nome_produto',
                'gpi.peso_total',
                'gpi.tempo_total',
                'gpi.custo_filamento',
                'gpi.custo_energia',
                'gpi.custo_desgaste',
                'gpi.custo_total',
                'gpi.status',
                'gpi.created_at',
                'gpi.updated_at',
                'pb.codigo_base',
                'pb.id as id_produto_base',
                'gp.descricao as descricao_grade',
                'gpc.descricao as descricao_combinacao',
                DB::raw("({$nomePartesSql}) as partes"),
            )
            ->join('grades_produtos as gp', 'gp.id', '=', 'gpi.id_grade_produto')
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->leftJoin('grade_produto_combinacoes as gpc', function ($join) {
                $join->on('gpc.id', '=', 'gpi.id_grade_produto_combinacao')
                    ->whereNull('gpc.deleted_at');
            })
            ->whereNull('gpi.deleted_at')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->where('gpi.id', $id)
            ->first();
    }

    public function getAsyncQuery(): Builder
    {
        return DB::table('grade_produto_itens as gpi')
            ->join('grades_produtos as gp', 'gp.id', '=', 'gpi.id_grade_produto')
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->leftJoin('grade_produto_combinacoes as gpc', function ($join) {
                $join->on('gpc.id', '=', 'gpi.id_grade_produto_combinacao')
                    ->whereNull('gpc.deleted_at');
            })
            ->whereNull('gpi.deleted_at')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->select(
                'gpi.id',
                'gpi.sku',
                'gpi.nome_produto',
                'pb.codigo_base',
            )
            ->orderBy('gpi.nome_produto');
    }

    public function getByGradeId(int $idGrade): Collection
    {
        return DB::table('grade_produto_itens as gpi')
            ->select(
                'gpi.id',
                'gpi.id_grade_produto',
                'gpi.id_grade_produto_combinacao',
                'gpi.nome_produto',
                'gpi.sku',
                'gpi.peso_total',
                'gpi.tempo_total',
                'gpi.custo_filamento',
                'gpi.custo_energia',
                'gpi.custo_desgaste',
                'gpi.custo_total',
                'gpi.status',
                'gpi.created_at',
                'gpc.descricao as descricao_combinacao',
            )
            ->leftJoin('grade_produto_combinacoes as gpc', function ($join) {
                $join->on('gpc.id', '=', 'gpi.id_grade_produto_combinacao')
                    ->whereNull('gpc.deleted_at');
            })
            ->where('gpi.id_grade_produto', $idGrade)
            ->whereNull('gpi.deleted_at')
            ->orderBy('gpi.nome_produto')
            ->get();
    }

    public function countByGradeId(int $idGrade): int
    {
        return GradeProdutoItem::where('id_grade_produto', $idGrade)
            ->whereNull('deleted_at')
            ->count();
    }

    private function sqlNomePartesGrade(string $gradeIdColumn): string
    {
        $partesGrade = "
            SELECT GROUP_CONCAT(DISTINCT parte.nome_parte ORDER BY parte.nome_parte SEPARATOR ' + ')
            FROM grade_produto_partes gpp
            INNER JOIN projetos_impressao_partes parte ON parte.id = gpp.id_parte_projeto
            WHERE gpp.id_grade_produto = {$gradeIdColumn}
              AND gpp.deleted_at IS NULL
              AND parte.deleted_at IS NULL
        ";

        $partesCombinacao = "
            SELECT GROUP_CONCAT(DISTINCT parte.nome_parte ORDER BY parte.nome_parte SEPARATOR ' + ')
            FROM grade_produto_combinacao_partes gpcp
            INNER JOIN grade_produto_combinacoes gpc ON gpc.id = gpcp.id_grade_produto_combinacao
            INNER JOIN projetos_impressao_partes parte ON parte.id = gpcp.id_parte_projeto
            WHERE gpc.id_grade_produto = {$gradeIdColumn}
              AND gpcp.deleted_at IS NULL
              AND gpc.deleted_at IS NULL
              AND parte.deleted_at IS NULL
        ";

        return "COALESCE(({$partesGrade}), ({$partesCombinacao}))";
    }
}
