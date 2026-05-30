<?php

namespace App\Services\ModeloProduto;

use App\Repositories\ModeloProduto\ModeloProdutoRepository;
use App\Services\PaginateService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ModeloProdutoService
{
    private ModeloProdutoRepository $_repository;

    public function __construct()
    {
        $this->_repository = new ModeloProdutoRepository();
    }

    public function handleLookupsModeloProduto(): array
    {
        return [];
    }

    public function handleAddModeloProduto(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->modeloProduto = $this->createModeloProduto($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditModeloProduto(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->modeloProduto = $this->updateModeloProduto($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteModeloProduto(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->modeloProduto = $this->deleteModeloProduto($id);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createModeloProduto(object $atributes): object
    {
        try {
            $this->validateCodigoUnico($atributes->codigo);

            $newData = $this->_repository->create([
                'descricao' => $atributes->descricao,
                'codigo'    => $atributes->codigo,
            ]);

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Modelo de produto cadastrado com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCodigoException($e)) {
                throw new Exception('Já existe uma modelo de produto com este código.', 422);
            }

            throw $e;
        }
    }

    public function updateModeloProduto(object $atributes): object
    {
        try {
            $record = $this->_repository->findById($atributes->id);

            if (!$record) {
                throw new Exception('Modelo de produto não encontrada', 404);
            }

            $this->validateCodigoUnico($atributes->codigo, $atributes->id);

            $saved = $this->_repository->update($record, [
                'descricao' => $atributes->descricao,
                'codigo'    => $atributes->codigo,
            ]);

            if (!$saved) {
                throw new Exception('Não foi possível editar modelo de produto', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Modelo de produto alterado com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCodigoException($e)) {
                throw new Exception('Já existe uma modelo de produto com este código.', 422);
            }

            throw $e;
        }
    }

    public function deleteModeloProduto(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Modelo de produto não encontrada', 404);
        }

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir modelo de produto', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Modelo de produto excluído com sucesso!',
        ];
    }

    public function getModeloProdutoPaginate(object $atributes): array
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

    public function getModeloProdutoId(int|string $id): array
    {
        $record = $this->_repository->getByIdQuery($id)->first();

        if (!$record) {
            throw new Exception('Modelo de produto não encontrada', 404);
        }

        return collect($record)->toArray();
    }

    public function getModeloProdutoAsync(object $params): array
    {
        $query = $this->_repository->getAsyncQuery();

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('ent.codigo', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
    }

    private function validateCodigoUnico(string $codigo, int|string|null $excludeId = null): void
    {
        if ($this->_repository->findByCodigo($codigo, $excludeId)) {
            throw new Exception('Já existe uma modelo de produto com este código.', 422);
        }
    }

    private function isDuplicateCodigoException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'modelos_produtos_codigo_unico');
    }

    private function applyFiltros($query, object $atributes): void
    {
        if (!empty($atributes->descricao)) {
            $chave = $atributes->descricao;
            $query->where('ent.descricao', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->codigo)) {
            $chave = $atributes->codigo;
            $query->where('ent.codigo', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('ent.codigo', 'like', '%' . $chave . '%');
            });
        }
    }
}
