<?php

namespace App\Services\CategoriaItem;

use App\Models\CategoriaItem;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class CategoriaItemService
{
    public function __construct()
    {
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsCategoriaItem(): array
    {
        $data = [];
        return $data;
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddCategoriaItem(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->categoriaItem = $this->createCategoriaItem($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditCategoriaItem(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->categoriaItem = $this->updateCategoriaItem($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteCategoriaItem(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->categoriaItem = $this->deleteCategoriaItem($id);

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

    public function createCategoriaItem(object $atributes): object
    {
        try {
            $newData = new CategoriaItem((array) $atributes);
            $saved   = $newData->save();

            if (!$saved) {
                throw new Exception('Não foi possível cadastrar Categoria de Item', 500);
            }

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Categoria de Item cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateCategoriaItem(object $atributes): object
    {
        try {
            $record = CategoriaItem::where('id', $atributes->id)->first();

            if (!$record) {
                throw new Exception('Categoria de Item não encontrada', 404);
            }

            $record->fill(get_object_vars($atributes));
            $saved = $record->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar Categoria de Item', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Categoria de Item alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteCategoriaItem(int|string $id): object
    {
        try {
            $record = CategoriaItem::where('id', $id)->first();

            if (!$record) {
                throw new Exception('Categoria de Item não encontrada', 404);
            }

            $saved = $record->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir Categoria de Item', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Categoria de Item excluída com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getCategoriaItemPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.descricao',
            'ent.created_at',
        );

        $query->from('categorias_itens as ent');
        $query->whereNull('ent.deleted_at');
        $query->orderBy('ent.descricao');

        if (!empty($atributes->descricao)) {
            $chave = $atributes->descricao;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao', 'like', '%' . $chave . '%');
            });
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao', 'like', '%' . $chave . '%');
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

    public function getCategoriaItemId(int|string $id): array
    {
        try {
            $query = DB::table('categorias_itens as ent')
                ->select(
                    'ent.id',
                    'ent.descricao',
                    'ent.created_at',
                )
                ->whereNull('ent.deleted_at')
                ->where('ent.id', $id);

            return collect($query->first())->toArray();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCategoriaItemAsync(object $params): array
    {
        $query = DB::table('categorias_itens as ent')
            ->whereNull('ent.deleted_at')
            ->select('ent.id', 'ent.descricao');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
    }
}
