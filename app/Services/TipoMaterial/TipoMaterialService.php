<?php

namespace App\Services\TipoMaterial;

use App\Models\TipoMaterial;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class TipoMaterialService
{
    public function __construct()
    {
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsTipoMaterial(): array
    {
        $data = [];
        return $data;
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddTipoMaterial(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                = (object) [];
            $result->tipoMaterial = $this->createTipoMaterial($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditTipoMaterial(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                = (object) [];
            $result->tipoMaterial = $this->updateTipoMaterial($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteTipoMaterial(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                = (object) [];
            $result->tipoMaterial = $this->deleteTipoMaterial($id);

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

    public function createTipoMaterial(object $atributes): object
    {
        try {
            $newData = new TipoMaterial((array) $atributes);
            $saved   = $newData->save();

            if (!$saved) {
                throw new Exception('Não foi possível cadastrar Tipo de Material', 500);
            }

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Tipo de Material cadastrado com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateTipoMaterial(object $atributes): object
    {
        try {
            $record = TipoMaterial::where('id', $atributes->id)->first();

            if (!$record) {
                throw new Exception('Tipo de Material não encontrado', 404);
            }

            $record->fill(get_object_vars($atributes));
            $saved = $record->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar Tipo de Material', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Tipo de Material alterado com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteTipoMaterial(int|string $id): object
    {
        try {
            $record = TipoMaterial::where('id', $id)->first();

            if (!$record) {
                throw new Exception('Tipo de Material não encontrado', 404);
            }

            $saved = $record->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir Tipo de Material', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Tipo de Material excluído com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getTipoMaterialPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.descricao',
            'ent.created_at',
        );

        $query->from('tipos_materiais as ent');
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

    public function getTipoMaterialId(int|string $id): array
    {
        try {
            $query = DB::table('tipos_materiais as ent')
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

    public function getTipoMaterialAsync(object $params): array
    {
        $query = DB::table('tipos_materiais as ent')
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
