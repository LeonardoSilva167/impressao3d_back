<?php

namespace App\Services\Item;

use App\Models\CategoriaItem;
use App\Models\Item;
use App\Services\PaginateService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ItemService
{
    public function __construct()
    {
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsItem(): array
    {
        return [
            'categoriasItens' => CategoriaItem::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao']),
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddItem(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result       = (object) [];
            $result->item = $this->createItem($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditItem(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result       = (object) [];
            $result->item = $this->updateItem($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteItem(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result       = (object) [];
            $result->item = $this->deleteItem($id);

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

    public function createItem(object $atributes): object
    {
        try {
            $this->validateCodigoUnico($atributes->codigo);
            $this->validateCategoriaItem((int) $atributes->id_categoria_item);

            $newData = new Item((array) $atributes);
            $saved   = $newData->save();

            if (!$saved) {
                throw new Exception('Não foi possível cadastrar Item', 500);
            }

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Item cadastrado com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCodigoException($e)) {
                throw new Exception('Já existe um item cadastrado com este código.', 422);
            }

            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateItem(object $atributes): object
    {
        try {
            $record = Item::where('id', $atributes->id)->first();

            if (!$record) {
                throw new Exception('Item não encontrado', 404);
            }

            $this->validateCodigoUnico($atributes->codigo, $atributes->id);
            $this->validateCategoriaItem((int) $atributes->id_categoria_item);

            $record->fill(get_object_vars($atributes));
            $saved = $record->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar Item', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Item alterado com sucesso!',
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateCodigoException($e)) {
                throw new Exception('Já existe um item cadastrado com este código.', 422);
            }

            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteItem(int|string $id): object
    {
        try {
            $record = Item::where('id', $id)->first();

            if (!$record) {
                throw new Exception('Item não encontrado', 404);
            }

            $saved = $record->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir Item', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Item excluído com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getItemPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.id_categoria_item',
            'cat.descricao as categoria_item_descricao',
            'ent.descricao',
            'ent.codigo',
            'ent.unidade_medida',
            'ent.controla_estoque',
            'ent.gera_custo',
            'ent.ativo',
            'ent.created_at',
        );

        $query->from('itens as ent');
        $query->join('categorias_itens as cat', 'cat.id', '=', 'ent.id_categoria_item');
        $query->whereNull('ent.deleted_at');
        $query->whereNull('cat.deleted_at');
        $query->orderBy('ent.descricao');

        if (!empty($atributes->descricao)) {
            $chave = $atributes->descricao;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao', 'like', '%' . $chave . '%');
            });
        }

        if (!empty($atributes->codigo)) {
            $chave = $atributes->codigo;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.codigo', 'like', '%' . $chave . '%');
            });
        }

        if (!empty($atributes->id_categoria_item)) {
            $query->where('ent.id_categoria_item', $atributes->id_categoria_item);
        }

        if (isset($atributes->ativo) && $atributes->ativo !== '') {
            $query->where('ent.ativo', filter_var($atributes->ativo, FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.descricao', 'like', '%' . $chave . '%')
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

    public function getItemId(int|string $id): array
    {
        try {
            $query = DB::table('itens as ent')
                ->join('categorias_itens as cat', 'cat.id', '=', 'ent.id_categoria_item')
                ->select(
                    'ent.id',
                    'ent.id_categoria_item',
                    'cat.descricao as categoria_item_descricao',
                    'ent.descricao',
                    'ent.codigo',
                    'ent.unidade_medida',
                    'ent.controla_estoque',
                    'ent.gera_custo',
                    'ent.ativo',
                    'ent.created_at',
                )
                ->whereNull('ent.deleted_at')
                ->whereNull('cat.deleted_at')
                ->where('ent.id', $id);

            $record = $query->first();

            if (!$record) {
                throw new Exception('Item não encontrado', 404);
            }

            return collect($record)->toArray();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getItemAsync(object $params): array
    {
        $query = DB::table('itens as ent')
            ->whereNull('ent.deleted_at')
            ->where('ent.ativo', true)
            ->select('ent.id', 'ent.descricao', 'ent.codigo');

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

    // =========================================================
    // HELPERS
    // =========================================================

    private function validateCodigoUnico(string $codigo, int|string|null $excludeId = null): void
    {
        $query = Item::where('codigo', $codigo);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new Exception('Já existe um item cadastrado com este código.', 422);
        }
    }

    private function validateCategoriaItem(int $idCategoriaItem): void
    {
        $exists = CategoriaItem::where('id', $idCategoriaItem)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            throw new Exception('Categoria de Item não encontrada', 422);
        }
    }

    private function isDuplicateCodigoException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'itens_codigo_unique');
    }
}
