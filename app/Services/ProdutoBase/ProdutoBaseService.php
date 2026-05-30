<?php

namespace App\Services\ProdutoBase;

use App\Models\CategoriaProduto;
use App\Models\LinhaProduto;
use App\Models\ModeloProduto;
use App\Repositories\Configuracao\ConfiguracaoRepository;
use App\Repositories\ProdutoBase\ProdutoBaseRepository;
use App\Repositories\ProdutoComposicao\ProdutoComposicaoRepository;
use App\Repositories\ProdutoComposicaoCor\ProdutoComposicaoCorRepository;
use App\Repositories\ProdutoVariacao\ProdutoVariacaoRepository;
use App\Repositories\ProdutoVariacaoFilamento\ProdutoVariacaoFilamentoRepository;
use App\Services\PaginateService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProdutoBaseService
{
    private ProdutoBaseRepository $_repository;

    private ProdutoComposicaoRepository $_composicaoRepository;

    private ProdutoComposicaoCorRepository $_composicaoCorRepository;

    private ProdutoVariacaoRepository $_variacaoRepository;

    private ProdutoVariacaoFilamentoRepository $_filamentoRepository;

    private ConfiguracaoRepository $_configuracaoRepository;

    public function __construct()
    {
        $this->_repository               = new ProdutoBaseRepository();
        $this->_composicaoRepository     = new ProdutoComposicaoRepository();
        $this->_composicaoCorRepository  = new ProdutoComposicaoCorRepository();
        $this->_variacaoRepository       = new ProdutoVariacaoRepository();
        $this->_filamentoRepository      = new ProdutoVariacaoFilamentoRepository();
        $this->_configuracaoRepository   = new ConfiguracaoRepository();
    }

    public function handleLookupsProdutoBase(): array
    {
        return [
            'categoriasProdutos' => CategoriaProduto::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'codigo']),
            'modelosProdutos' => ModeloProduto::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'codigo']),
            'linhasProdutos' => LinhaProduto::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'codigo']),
            'proximoCodigoBase' => $this->_configuracaoRepository->getProximoCodigoBase(),
        ];
    }

    public function handleAddProdutoBase(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result               = (object) [];
            $result->produtoBase = $this->createProdutoBase($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditProdutoBase(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result               = (object) [];
            $result->produtoBase = $this->updateProdutoBase($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteProdutoBase(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result               = (object) [];
            $result->produtoBase = $this->deleteProdutoBase($id);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createProdutoBase(object $atributes): object
    {
        try {
            $codigoBase = $this->_configuracaoRepository->consumirProximoCodigoBase();
            $idLinha    = $this->resolveIdLinha($atributes);

            $skuBase = $this->buildSkuBase(
                $codigoBase,
                (int) $atributes->id_categoria,
                (int) $atributes->id_modelo,
                $idLinha
            );

            $this->validateSkuBaseUnico($skuBase);

            $newData = $this->_repository->create([
                'descricao_produto' => $atributes->descricao_produto,
                'codigo_base'       => $codigoBase,
                'sku_base'          => $skuBase,
                'id_categoria'      => (int) $atributes->id_categoria,
                'id_modelo'         => (int) $atributes->id_modelo,
                'id_linha'          => $idLinha,
            ]);

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Produto base cadastrado com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateSkuBaseException($e)) {
                throw new Exception('Já existe um produto base com este SKU.', 422);
            }

            throw $e;
        }
    }

    public function updateProdutoBase(object $atributes): object
    {
        try {
            $record = $this->_repository->findById($atributes->id);

            if (!$record) {
                throw new Exception('Produto base não encontrado', 404);
            }

            $idLinha = $this->resolveIdLinha($atributes);

            $skuBase = $this->buildSkuBase(
                $record->codigo_base,
                (int) $atributes->id_categoria,
                (int) $atributes->id_modelo,
                $idLinha
            );

            $this->validateSkuBaseUnico($skuBase, $atributes->id);

            $saved = $this->_repository->update($record, [
                'descricao_produto' => $atributes->descricao_produto,
                'sku_base'          => $skuBase,
                'id_categoria'      => (int) $atributes->id_categoria,
                'id_modelo'         => (int) $atributes->id_modelo,
                'id_linha'          => $idLinha,
            ]);

            if (!$saved) {
                throw new Exception('Não foi possível editar produto base', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Produto base alterado com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateSkuBaseException($e)) {
                throw new Exception('Já existe um produto base com este SKU.', 422);
            }

            throw $e;
        }
    }

    public function deleteProdutoBase(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Produto base não encontrado', 404);
        }

        $this->removerComposicoesPorProduto((int) $record->id);

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir produto base', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Produto base excluído com sucesso!',
        ];
    }

    public function getProdutoBasePaginate(object $atributes): array
    {
        $query = $this->_repository->getPaginateQuery();
        $this->applyFiltros($query, $atributes);

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

    public function getProdutoBaseId(int|string $id): array
    {
        $record = $this->_repository->findByIdWithRelations($id);

        if (!$record) {
            throw new Exception('Produto base não encontrado', 404);
        }

        return [
            'id'                => (int) $record->id,
            'descricao_produto' => $record->descricao_produto,
            'codigo_base'       => $record->codigo_base,
            'sku_base'          => $record->sku_base,
            'id_categoria'      => (int) $record->id_categoria,
            'id_modelo'         => (int) $record->id_modelo,
            'id_linha'          => $record->id_linha !== null ? (int) $record->id_linha : null,
            'created_at'        => $record->created_at,
            'categoria'         => [
                'descricao' => $record->categoria_descricao,
                'codigo'    => $record->categoria_codigo,
            ],
            'modelo'            => [
                'descricao' => $record->modelo_descricao,
                'codigo'    => $record->modelo_codigo,
            ],
            'linha'             => $record->id_linha !== null ? [
                'descricao' => $record->linha_descricao,
                'codigo'    => $record->linha_codigo,
            ] : null,
        ];
    }

    public function getProdutoBaseAsync(object $params): array
    {
        $query = $this->_repository->getAsyncQuery();

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao_produto', 'like', '%' . $chave . '%')
                    ->orWhere('ent.sku_base', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
    }

    public function buildSkuBase(
        string $codigoBase,
        int $idCategoria,
        int $idModelo,
        ?int $idLinha = null
    ): string {
        $codigos = $this->_repository->getCodigosRelacionamentos($idCategoria, $idModelo, $idLinha);

        if (!$codigos) {
            throw new Exception('Não foi possível montar o SKU base. Verifique categoria, modelo e linha informados.', 422);
        }

        $partes = [
            $codigoBase,
            $codigos->codigo_categoria,
            $codigos->codigo_modelo,
        ];

        if ($codigos->codigo_linha !== null) {
            $partes[] = $codigos->codigo_linha;
        }

        return implode('-', $partes);
    }

    private function removerComposicoesPorProduto(int $idProduto): void
    {
        $composicoes = DB::table('produto_composicoes')
            ->where('id_produto', $idProduto)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($composicoes as $idComposicao) {
            $this->_filamentoRepository->deleteByComposicaoId($idComposicao);
            $this->_variacaoRepository->deleteByComposicaoId($idComposicao);
            $this->_composicaoCorRepository->deleteByComposicaoId($idComposicao);
        }

        $this->_composicaoRepository->softDeleteByProdutoId($idProduto);
    }

    private function resolveIdLinha(object $atributes): ?int
    {
        if (!isset($atributes->id_linha) || $atributes->id_linha === null || $atributes->id_linha === '') {
            return null;
        }

        return (int) $atributes->id_linha;
    }

    private function validateSkuBaseUnico(string $skuBase, int|string|null $excludeId = null): void
    {
        if ($this->_repository->findBySkuBase($skuBase, $excludeId)) {
            throw new Exception('Já existe um produto base com este SKU.', 422);
        }
    }

    private function isDuplicateSkuBaseException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'produtos_base_sku_base_unico');
    }

    private function applyFiltros($query, object $atributes): void
    {
        if (!empty($atributes->descricao_produto)) {
            $chave = $atributes->descricao_produto;
            $query->where('ent.descricao_produto', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->sku_base)) {
            $chave = $atributes->sku_base;
            $query->where('ent.sku_base', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->codigo_base)) {
            $chave = $atributes->codigo_base;
            $query->where('ent.codigo_base', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao_produto', 'like', '%' . $chave . '%')
                    ->orWhere('ent.sku_base', 'like', '%' . $chave . '%')
                    ->orWhere('ent.codigo_base', 'like', '%' . $chave . '%');
            });
        }
    }
}
