<?php

namespace App\Services\ParteBase;

use App\Repositories\ParteBase\ParteBaseRepository;
use App\Services\PaginateService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ParteBaseService
{
    private ParteBaseRepository $_repository;

    public function __construct()
    {
        $this->_repository = new ParteBaseRepository();
    }

    public function handleLookupsParteBase(): array
    {
        return [];
    }

    public function handleAddParteBase(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->parteBase = $this->createParteBase($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditParteBase(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->parteBase = $this->updateParteBase($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteParteBase(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->parteBase = $this->deleteParteBase($id);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createParteBase(object $atributes): object
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
                'message' => 'Parte base cadastrada com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCodigoException($e)) {
                throw new Exception('Já existe uma parte base com este código.', 422);
            }

            throw $e;
        }
    }

    public function updateParteBase(object $atributes): object
    {
        try {
            $record = $this->_repository->findById($atributes->id);

            if (!$record) {
                throw new Exception('Parte base não encontrada', 404);
            }

            $this->validateCodigoUnico($atributes->codigo, $atributes->id);

            $saved = $this->_repository->update($record, [
                'descricao' => $atributes->descricao,
                'codigo'    => $atributes->codigo,
            ]);

            if (!$saved) {
                throw new Exception('Não foi possível editar parte base', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Parte base alterada com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCodigoException($e)) {
                throw new Exception('Já existe uma parte base com este código.', 422);
            }

            throw $e;
        }
    }

    public function deleteParteBase(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Parte base não encontrada', 404);
        }

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir parte base', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Parte base excluída com sucesso!',
        ];
    }

    public function getParteBasePaginate(object $atributes): array
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

    public function getParteBaseId(int|string $id): array
    {
        $record = $this->_repository->getByIdQuery($id)->first();

        if (!$record) {
            throw new Exception('Parte base não encontrada', 404);
        }

        return collect($record)->toArray();
    }

    public function getParteBaseAsync(object $params): array
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
            throw new Exception('Já existe uma parte base com este código.', 422);
        }
    }

    private function isDuplicateCodigoException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'partes_base_codigo_unico');
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
