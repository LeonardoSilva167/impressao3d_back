<?php

namespace App\Services\ProdutoVariacao;

use App\Models\Cor;
use App\Models\ProdutoBase;
use App\Models\ProdutoVariacao;
use App\Repositories\ProdutoBase\ProdutoBaseRepository;
use App\Repositories\ProdutoVariacao\ProdutoVariacaoRepository;
use App\Services\PaginateService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProdutoVariacaoService
{
    private ProdutoVariacaoRepository $_repository;
    private ProdutoBaseRepository $_produtoBaseRepository;

    public function __construct()
    {
        $this->_repository            = new ProdutoVariacaoRepository();
        $this->_produtoBaseRepository = new ProdutoBaseRepository();
    }

    public function handleLookupsProdutoVariacao(): array
    {
        return [
            'produtosBase' => ProdutoBase::whereNull('deleted_at')
                ->orderBy('descricao_produto')
                ->get(['id', 'descricao_produto', 'sku_base']),
            'cores' => Cor::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'codigo']),
        ];
    }

    public function handleAddProdutoVariacao(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->produtoVariacao = $this->syncVariacoes($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditProdutoVariacao(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->produtoVariacao = $this->syncVariacoes($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteProdutoVariacao(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->produtoVariacao = $this->deleteProdutoVariacao($id);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function syncVariacoes(object $atributes): object
    {
        try {
            $idProdutoBase = (int) $atributes->id_produto_base;
            $produtoBase   = $this->_produtoBaseRepository->findById($idProdutoBase);

            if (!$produtoBase) {
                throw new Exception('Produto base não encontrado.', 404);
            }

            $combinacoes = $this->gerarCombinacoesCores($atributes);
            $this->validarCombinacoesSemDuplicidade($combinacoes);

            $chavesNovas    = [];
            $criadas        = 0;
            $reativadas     = 0;
            $inativadas     = 0;

            foreach ($combinacoes as $combinacao) {
                $chave = $this->chaveCombinacao(
                    $idProdutoBase,
                    $combinacao['id_cor_primaria'],
                    $combinacao['id_cor_secundaria'],
                    $combinacao['id_cor_terciaria']
                );
                $chavesNovas[$chave] = true;

                $sku = $this->buildSkuVariacao(
                    $idProdutoBase,
                    $combinacao['id_cor_primaria'],
                    $combinacao['id_cor_secundaria'],
                    $combinacao['id_cor_terciaria']
                );

                $existente = $this->_repository->findByCombinacao(
                    $idProdutoBase,
                    $combinacao['id_cor_primaria'],
                    $combinacao['id_cor_secundaria'],
                    $combinacao['id_cor_terciaria'],
                    true
                );

                if ($existente) {
                    if ($existente->deleted_at !== null) {
                        throw new Exception(
                            'Combinação de cores já existente e foi excluída anteriormente. Não é possível recriar automaticamente.',
                            422
                        );
                    }

                    if ($existente->status === ProdutoVariacao::STATUS_INATIVADA) {
                        $this->_repository->update($existente, [
                            'status' => ProdutoVariacao::STATUS_ATIVA,
                            'sku'    => $sku,
                        ]);
                        $reativadas++;
                        continue;
                    }

                    if ($existente->sku !== $sku) {
                        $this->validateSkuUnico($sku, $existente->id);
                        $this->_repository->update($existente, ['sku' => $sku]);
                    }

                    continue;
                }

                $this->validateSkuUnico($sku);

                $this->_repository->create([
                    'id_produto_base'   => $idProdutoBase,
                    'id_cor_primaria'   => $combinacao['id_cor_primaria'],
                    'id_cor_secundaria' => $combinacao['id_cor_secundaria'],
                    'id_cor_terciaria'  => $combinacao['id_cor_terciaria'],
                    'sku'               => $sku,
                    'status'            => ProdutoVariacao::STATUS_ATIVA,
                ]);
                $criadas++;
            }

            $variacoesExistentes = $this->_repository->getByProdutoBaseId($idProdutoBase);

            foreach ($variacoesExistentes as $variacao) {
                $chave = $this->chaveCombinacao(
                    $idProdutoBase,
                    (int) $variacao->id_cor_primaria,
                    $variacao->id_cor_secundaria !== null ? (int) $variacao->id_cor_secundaria : null,
                    $variacao->id_cor_terciaria !== null ? (int) $variacao->id_cor_terciaria : null
                );

                if (
                    !isset($chavesNovas[$chave])
                    && $variacao->status === ProdutoVariacao::STATUS_ATIVA
                ) {
                    $this->_repository->update($variacao, [
                        'status' => ProdutoVariacao::STATUS_INATIVADA,
                    ]);
                    $inativadas++;
                }
            }

            return (object) [
                'data'    => [
                    'id_produto_base' => $idProdutoBase,
                    'total_combinacoes' => count($combinacoes),
                    'criadas'           => $criadas,
                    'reativadas'        => $reativadas,
                    'inativadas'        => $inativadas,
                ],
                'status'  => true,
                'message' => 'Variações sincronizadas com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCombinacaoException($e) || $this->isDuplicateSkuException($e)) {
                throw new Exception('Já existe uma variação com esta combinação de cores.', 422);
            }

            throw $e;
        }
    }

    public function deleteProdutoVariacao(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Variação de produto não encontrada', 404);
        }

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir variação de produto', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Variação de produto excluída com sucesso!',
        ];
    }

    public function getProdutoVariacaoPaginate(object $atributes): array
    {
        $query = $this->_repository->getPaginateQuery();

        if (!empty($atributes->id_produto_base)) {
            $query->where('pv.id_produto_base', (int) $atributes->id_produto_base);
        }

        if (!empty($atributes->sku)) {
            $chave = $atributes->sku;
            $query->where('pv.sku', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('pv.sku', 'like', '%' . $chave . '%')
                    ->orWhere('pb.descricao_produto', 'like', '%' . $chave . '%');
            });
        }

        $paginate  = new PaginateService();
        $resultado = $paginate->_paginate(
            $query,
            $atributes->page,
            $atributes->perPage,
            ['path' => $atributes->url, 'query' => $atributes->query]
        );
        $resultado->appends((array) $atributes);

        return collect($resultado)->toArray();
    }

    public function getProdutoVariacaoId(int|string $id): array
    {
        $record = $this->_repository->findByIdWithRelations($id);

        if (!$record) {
            throw new Exception('Variação de produto não encontrada', 404);
        }

        return collect($record)->toArray();
    }

    public function getProdutoVariacaoAsync(object $params): array
    {
        $query = $this->_repository->getAsyncQuery();

        if (!empty($params->id_produto_base)) {
            $query->where('pv.id_produto_base', (int) $params->id_produto_base);
        }

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where('pv.sku', 'like', '%' . $chave . '%');
            $query->limit(10);
        }

        return $query->get()->toArray();
    }

    public function buildSkuVariacao(
        int $idProdutoBase,
        int $idCorPrimaria,
        ?int $idCorSecundaria = null,
        ?int $idCorTerciaria = null
    ): string {
        $produtoBase = $this->_produtoBaseRepository->findById($idProdutoBase);

        if (!$produtoBase) {
            throw new Exception('Produto base não encontrado.', 404);
        }

        $codigos = $this->_repository->getCodigosCores($idCorPrimaria, $idCorSecundaria, $idCorTerciaria);

        if (!$codigos) {
            throw new Exception('Não foi possível montar o SKU da variação. Verifique as cores informadas.', 422);
        }

        $partes = [
            $produtoBase->sku_base,
            $codigos->codigo_primaria,
        ];

        if ($codigos->codigo_secundaria !== null) {
            $partes[] = $codigos->codigo_secundaria;
        }

        if ($codigos->codigo_terciaria !== null) {
            $partes[] = $codigos->codigo_terciaria;
        }

        return implode('-', $partes);
    }

    private function gerarCombinacoesCores(object $atributes): array
    {
        $primarias   = $this->normalizarListaCores($atributes->cores_primarias ?? []);
        $secundarias = $this->normalizarListaCores($atributes->cores_secundarias ?? []);
        $terciarias  = $this->normalizarListaCores($atributes->cores_terciarias ?? []);

        if (empty($primarias)) {
            throw new Exception('Informe ao menos uma cor primária.', 422);
        }

        $secundarias = !empty($secundarias) ? $secundarias : [null];
        $terciarias  = !empty($terciarias) ? $terciarias : [null];

        $combinacoes = [];

        foreach ($primarias as $primaria) {
            foreach ($secundarias as $secundaria) {
                foreach ($terciarias as $terciaria) {
                    $combinacoes[] = [
                        'id_cor_primaria'   => $primaria,
                        'id_cor_secundaria' => $secundaria,
                        'id_cor_terciaria'  => $terciaria,
                    ];
                }
            }
        }

        return $combinacoes;
    }

    private function normalizarListaCores(array $cores): array
    {
        $ids = array_values(array_unique(array_map('intval', $cores)));

        return array_filter($ids, fn (int $id) => $id > 0);
    }

    private function validarCombinacoesSemDuplicidade(array $combinacoes): void
    {
        $chaves = [];

        foreach ($combinacoes as $combinacao) {
            $chave = $this->chaveCombinacao(
                0,
                $combinacao['id_cor_primaria'],
                $combinacao['id_cor_secundaria'],
                $combinacao['id_cor_terciaria']
            );

            if (isset($chaves[$chave])) {
                throw new Exception('Combinação de cores duplicada na requisição.', 422);
            }

            $chaves[$chave] = true;
        }
    }

    private function chaveCombinacao(
        int $idProdutoBase,
        int $idCorPrimaria,
        ?int $idCorSecundaria,
        ?int $idCorTerciaria
    ): string {
        return implode(':', [
            $idProdutoBase,
            $idCorPrimaria,
            $idCorSecundaria ?? 'null',
            $idCorTerciaria ?? 'null',
        ]);
    }

    private function validateSkuUnico(string $sku, int|string|null $excludeId = null): void
    {
        if ($this->_repository->findBySku($sku, $excludeId)) {
            throw new Exception('Já existe uma variação com este SKU: ' . $sku, 422);
        }
    }

    private function isDuplicateSkuException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'produto_variacoes_sku_unico');
    }

    private function isDuplicateCombinacaoException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'produto_variacoes_combinacao_unica');
    }
}
