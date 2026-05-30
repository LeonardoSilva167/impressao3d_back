<?php

namespace App\Repositories\GradeProduto;

use App\Models\GradeProduto;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class GradeProdutoRepository
{
    public function findById(int|string $id): ?GradeProduto
    {
        return GradeProduto::where('id', $id)->whereNull('deleted_at')->first();
    }

    public function create(array $data): GradeProduto
    {
        return GradeProduto::create($data);
    }

    public function update(GradeProduto $record, array $data): bool
    {
        return $record->update($data);
    }

    public function delete(GradeProduto $record): bool
    {
        return (bool) $record->delete();
    }

    public function getPaginateQuery(): Builder
    {
        $nomePartesSql = $this->sqlNomePartesGrade('gp.id');

        return DB::query()
            ->select(
                'gp.id',
                'gp.id_produto_base',
                'gp.descricao',
                'gp.status',
                'gp.created_at',
                'pb.codigo_base',
                DB::raw("({$nomePartesSql}) as nome_parte"),
                DB::raw('(SELECT COUNT(*) FROM grade_produto_combinacoes gpc WHERE gpc.id_grade_produto = gp.id AND gpc.deleted_at IS NULL) as quantidade_combinacoes'),
                DB::raw('(SELECT COUNT(*) FROM grade_produto_itens gpi WHERE gpi.id_grade_produto = gp.id AND gpi.deleted_at IS NULL) as quantidade_produtos'),
            )
            ->from('grades_produtos as gp')
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->orderByDesc('gp.created_at');
    }

    /**
     * Nomes das partes da grade (grade_produto_partes), com fallback nas combinações.
     */
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

    public function findByIdWithRelations(int|string $id): ?object
    {
        return DB::table('grades_produtos as gp')
            ->select(
                'gp.id',
                'gp.id_produto_base',
                'gp.descricao',
                'gp.status',
                'gp.created_at',
                'gp.updated_at',
                'pb.descricao_produto',
                'pb.sku_base',
                'pb.codigo_base',
            )
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->where('gp.id', $id)
            ->first();
    }

    public function getAsyncQuery(): Builder
    {
        $nomePartesSql = $this->sqlNomePartesGrade('gp.id');

        return DB::table('grades_produtos as gp')
            ->join('produtos_base as pb', 'pb.id', '=', 'gp.id_produto_base')
            ->whereNull('gp.deleted_at')
            ->whereNull('pb.deleted_at')
            ->select(
                'gp.id',
                'gp.descricao',
                'pb.codigo_base',
                DB::raw("({$nomePartesSql}) as nome_parte"),
            )
            ->orderBy('gp.descricao');
    }
}
