<?php

namespace App\Services\FluxoProducao;

use App\Repositories\GradeProduto\GradeProdutoRepository;
use App\Repositories\ProdutoBase\ProdutoBaseRepository;
use App\Repositories\ProdutoComposicao\ProdutoComposicaoRepository;
use App\Repositories\ProjetoImpressao\ProjetoImpressaoRepository;
use App\Services\ProdutoComposicao\ProdutoComposicaoService;
use Exception;

class FluxoProducaoService
{
    private const SUBPASSOS_ORDEM = [
        'E1_PRODUTO',
        'E2_PROJETO',
        'E2_VINCULO',
        'E2_PARTES',
        'E3_MONTAGEM',
    ];

    private ProdutoBaseRepository $_produtoRepository;

    private ProdutoComposicaoRepository $_composicaoRepository;

    private ProjetoImpressaoRepository $_projetoRepository;

    private GradeProdutoRepository $_gradeRepository;

    private ProdutoComposicaoService $_composicaoService;

    public function __construct()
    {
        $this->_produtoRepository    = new ProdutoBaseRepository();
        $this->_composicaoRepository = new ProdutoComposicaoRepository();
        $this->_projetoRepository    = new ProjetoImpressaoRepository();
        $this->_gradeRepository      = new GradeProdutoRepository();
        $this->_composicaoService    = new ProdutoComposicaoService();
    }

    public function handleLookupsFluxoProducao(): array
    {
        return [
            'subpassos' => self::SUBPASSOS_ORDEM,
        ];
    }

    public function getProgresso(object $params): array
    {
        $produtoId = (int) ($params->produto ?? 0);

        if ($produtoId <= 0) {
            throw new Exception('O parâmetro produto é obrigatório.', 422);
        }

        $produto = $this->_produtoRepository->findById($produtoId);

        if (!$produto) {
            throw new Exception('Produto não encontrado.', 404);
        }

        $composicaoQueryId = (int) ($params->composicao ?? 0);
        $projetoQueryId    = (int) ($params->projeto ?? 0);

        $composicao = $this->resolverComposicao($produtoId, $composicaoQueryId);
        $composicaoId = $composicao ? (int) $composicao->id : null;

        $projetoId = null;
        $projeto   = null;

        if ($composicao) {
            $projetoId = (int) $composicao->id_projeto_impressao;
            $projeto   = $this->_projetoRepository->findById($projetoId);

            if ($projetoQueryId > 0 && $projetoQueryId !== $projetoId) {
                throw new Exception('O projeto informado não corresponde ao vínculo do produto.', 422);
            }
        } elseif ($projetoQueryId > 0) {
            $projeto = $this->_projetoRepository->findById($projetoQueryId);

            if (!$projeto) {
                throw new Exception('Projeto não encontrado.', 404);
            }

            $projetoId = (int) $projeto->id;
        }

        $grade   = $this->_gradeRepository->findMaisRecenteByProdutoBaseId($produtoId);
        $gradeId = $grade ? (int) $grade->id : null;

        $partesResumo = [];

        if ($composicaoId && $projeto) {
            $partesResumo = $this->_composicaoService->getPartesResumoComposicao($composicaoId, (int) $projeto->id);
        }

        $subpassos = [
            'E1_PRODUTO'  => true,
            'E2_PROJETO'  => $projetoId !== null && $projeto !== null,
            'E2_VINCULO'  => $composicaoId !== null,
            'E2_PARTES'   => $this->todasPartesConfiguradas($partesResumo),
            'E3_MONTAGEM' => $gradeId !== null,
        ];

        return [
            'produto_id'       => $produtoId,
            'projeto_id'       => $projetoId,
            'composicao_id'    => $composicaoId,
            'grade_id'         => $gradeId,
            'produto'          => [
                'id'                => (int) $produto->id,
                'descricao_produto' => $produto->descricao_produto,
                'sku_base'          => $produto->sku_base,
                'codigo_base'       => $produto->codigo_base,
            ],
            'projeto'          => $projeto ? [
                'id'                     => (int) $projeto->id,
                'nome_original_projeto'  => $projeto->nome_original_projeto,
                'codigo_projeto'         => $projeto->codigo_projeto,
            ] : null,
            'partes_resumo'    => $partesResumo,
            'subpassos'        => $subpassos,
            'proximo_subpasso' => $this->resolverProximoSubpasso($subpassos),
        ];
    }

    private function resolverComposicao(int $produtoId, int $composicaoQueryId)
    {
        if ($composicaoQueryId > 0) {
            $composicao = $this->_composicaoRepository->findById($composicaoQueryId);

            if (!$composicao) {
                throw new Exception('Composição não encontrada.', 404);
            }

            if ((int) $composicao->id_produto !== $produtoId) {
                throw new Exception('A composição informada não pertence ao produto.', 422);
            }

            return $composicao;
        }

        return $this->_composicaoRepository->findMaisRecenteByProdutoId($produtoId);
    }

    private function todasPartesConfiguradas(array $partesResumo): bool
    {
        if (empty($partesResumo)) {
            return false;
        }

        foreach ($partesResumo as $parte) {
            if (empty($parte['configurada'])) {
                return false;
            }
        }

        return true;
    }

    private function resolverProximoSubpasso(array $subpassos): ?string
    {
        foreach (self::SUBPASSOS_ORDEM as $codigo) {
            if (empty($subpassos[$codigo])) {
                return $codigo;
            }
        }

        return null;
    }
}
