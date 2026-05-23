<?php

namespace App\Services\Filamento;

use App\Models\CategoriaItem;
use App\Models\Cor;
use App\Models\Item;
use App\Models\LinhaMarca;
use App\Models\Marca;
use App\Models\TipoMaterial;
use App\Repositories\Filamento\FilamentoRepository;
use App\Services\PaginateService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class FilamentoService
{
    /**
     * @var FilamentoRepository $_repository
     */
    private FilamentoRepository $_repository;

    public function __construct()
    {
        $this->_repository = new FilamentoRepository();
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsFilamento(): array
    {
        return [
            'tiposMateriais' => TipoMaterial::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
            'cores' => Cor::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
            'linhasMarcas' => LinhaMarca::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
            'marcas' => Marca::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddFilamento(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result             = (object) [];
            $result->filamento = $this->createFilamento($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditFilamento(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result             = (object) [];
            $result->filamento = $this->updateFilamento($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteFilamento(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result             = (object) [];
            $result->filamento = $this->deleteFilamento($id);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // =========================================================
    // CRUD FUNCTIONS
    // =========================================================

    public function createFilamento(object $atributes): object
    {
        try {
            $this->validateCombinacaoUnica(
                (int) $atributes->id_tipo_material,
                (int) $atributes->id_cor,
                (int) $atributes->id_linha_marca,
                (int) $atributes->id_marca
            );

            $resumo = $this->buildResumo(
                (int) $atributes->id_tipo_material,
                (int) $atributes->id_cor,
                (int) $atributes->id_linha_marca,
                (int) $atributes->id_marca
            );

            $codigo = $this->_repository->generateNextCodigo();
            $item   = $this->createItemForFilamento($resumo, $codigo);

            $data = [
                'id_tipo_material'  => (int) $atributes->id_tipo_material,
                'id_cor'            => (int) $atributes->id_cor,
                'id_linha_marca'    => (int) $atributes->id_linha_marca,
                'id_marca'          => (int) $atributes->id_marca,
                'id_item'           => $item->id,
                'codigo'            => $codigo,
                'resumo'            => $resumo,
                'qtd'               => $atributes->qtd ?? 0,
                'preco_medio_grama' => $atributes->preco_medio_grama ?? 0,
            ];

            $newData = $this->_repository->create($data);

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Filamento cadastrado com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCombinacaoException($e)) {
                throw new Exception(
                    'Já existe um filamento cadastrado com esta combinação de tipo de material, cor, linha e marca.',
                    422
                );
            }

            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateFilamento(object $atributes): object
    {
        try {
            $record = $this->_repository->findById($atributes->id);

            if (!$record) {
                throw new Exception('Filamento não encontrado', 404);
            }

            $this->validateCombinacaoUnica(
                (int) $atributes->id_tipo_material,
                (int) $atributes->id_cor,
                (int) $atributes->id_linha_marca,
                (int) $atributes->id_marca,
                (int) $atributes->id
            );

            $resumo = $this->buildResumo(
                (int) $atributes->id_tipo_material,
                (int) $atributes->id_cor,
                (int) $atributes->id_linha_marca,
                (int) $atributes->id_marca
            );

            $data = [
                'id_tipo_material'  => (int) $atributes->id_tipo_material,
                'id_cor'            => (int) $atributes->id_cor,
                'id_linha_marca'    => (int) $atributes->id_linha_marca,
                'id_marca'          => (int) $atributes->id_marca,
                'resumo'            => $resumo,
                'qtd'               => property_exists($atributes, 'qtd') ? ($atributes->qtd ?? 0) : $record->qtd,
                'preco_medio_grama' => property_exists($atributes, 'preco_medio_grama') ? ($atributes->preco_medio_grama ?? 0) : $record->preco_medio_grama,
            ];

            $saved = $this->_repository->update($record, $data);

            if (!$saved) {
                throw new Exception('Não foi possível editar Filamento', 500);
            }

            $this->updateItemForFilamento((int) $record->id_item, $resumo);

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Filamento alterado com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCombinacaoException($e)) {
                throw new Exception(
                    'Já existe um filamento cadastrado com esta combinação de tipo de material, cor, linha e marca.',
                    422
                );
            }

            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteFilamento(int|string $id): object
    {
        try {
            $record = $this->_repository->findById($id);

            if (!$record) {
                throw new Exception('Filamento não encontrado', 404);
            }

            $this->deleteItemForFilamento((int) $record->id_item);

            $saved = $this->_repository->delete($record);

            if (!$saved) {
                throw new Exception('Não foi possível excluir Filamento', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Filamento excluído com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getFilamentoPaginate(object $atributes): array
    {
        $query = $this->_repository->getPaginateQuery();

        if (!empty($atributes->resumo)) {
            $chave = $atributes->resumo;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.resumo', 'like', '%' . $chave . '%');
            });
        }

        if (!empty($atributes->codigo)) {
            $chave = $atributes->codigo;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.codigo', 'like', '%' . $chave . '%');
            });
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.resumo', 'like', '%' . $chave . '%')
                    ->orWhere('ent.codigo', 'like', '%' . $chave . '%');
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

    public function getFilamentoId(int|string $id): array
    {
        try {
            $record = $this->_repository->findByIdWithRelations($id);

            if (!$record) {
                throw new Exception('Filamento não encontrado', 404);
            }

            return collect($record)->toArray();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getFilamentoAsync(object $params): array
    {
        $query = $this->_repository->getAsyncQuery();

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.resumo', 'like', '%' . $chave . '%')
                    ->orWhere('ent.codigo', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function validateCombinacaoUnica(
        int $idTipoMaterial,
        int $idCor,
        int $idLinhaMarca,
        int $idMarca,
        int|string|null $excludeId = null
    ): void {
        $existing = $this->_repository->findByCombinacao(
            $idTipoMaterial,
            $idCor,
            $idLinhaMarca,
            $idMarca,
            $excludeId
        );

        if ($existing) {
            throw new Exception(
                'Já existe um filamento cadastrado com esta combinação de tipo de material, cor, linha e marca.',
                422
            );
        }
    }

    private function buildResumo(
        int $idTipoMaterial,
        int $idCor,
        int $idLinhaMarca,
        int $idMarca
    ): string {
        $descricoes = $this->_repository->getDescricoesRelacionamentos(
            $idTipoMaterial,
            $idCor,
            $idLinhaMarca,
            $idMarca
        );

        if (!$descricoes) {
            throw new Exception('Não foi possível montar o resumo do filamento. Verifique os relacionamentos informados.', 422);
        }

        return implode(' ', [
            $descricoes->tipo_material_descricao,
            $descricoes->cor_descricao,
            $descricoes->linha_marca_descricao,
            $descricoes->marca_descricao,
        ]);
    }

    private function isDuplicateCombinacaoException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'filamentos_combinacao_unica');
    }

    private function getCategoriaFilamentoId(): int
    {
        $id = CategoriaItem::where('descricao', 'FILAMENTO')
            ->whereNull('deleted_at')
            ->value('id');

        if ($id === null) {
            throw new Exception('Categoria FILAMENTO não encontrada.', 422);
        }

        return (int) $id;
    }

    private function createItemForFilamento(string $resumo, string $codigo): Item
    {
        $item = Item::create([
            'id_categoria_item' => $this->getCategoriaFilamentoId(),
            'descricao'         => $resumo,
            'codigo'            => $codigo,
            'unidade_medida'    => 'g',
            'controla_estoque'  => true,
            'gera_custo'        => true,
            'ativo'             => true,
        ]);

        return $item;
    }

    private function updateItemForFilamento(int $idItem, string $resumo): void
    {
        $item = Item::where('id', $idItem)->first();

        if (!$item) {
            throw new Exception('Item vinculado ao filamento não encontrado.', 404);
        }

        $item->descricao = $resumo;
        $saved           = $item->save();

        if (!$saved) {
            throw new Exception('Não foi possível atualizar o item vinculado ao filamento.', 500);
        }
    }

    private function deleteItemForFilamento(int $idItem): void
    {
        $item = Item::where('id', $idItem)->first();

        if (!$item) {
            throw new Exception('Item vinculado ao filamento não encontrado.', 404);
        }

        $saved = $item->delete();

        if (!$saved) {
            throw new Exception('Não foi possível excluir o item vinculado ao filamento.', 500);
        }
    }
}
