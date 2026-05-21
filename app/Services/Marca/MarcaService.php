<?php

namespace App\Services\Marca;

use App\Models\Marca;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class MarcaService
{
    public function __construct()
    {
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsMarca(): array
    {
        $data = [];
        return $data;
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddMarca(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result        = (object) [];
            $result->marca = $this->createMarca($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditMarca(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result        = (object) [];
            $result->marca = $this->updateMarca($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteMarca(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result        = (object) [];
            $result->marca = $this->deleteMarca($id);

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

    public function createMarca(object $atributes): object
    {
        try {
            $newData = new Marca((array) $atributes);
            $saved   = $newData->save();

            if (!$saved) {
                throw new Exception('Não foi possível cadastrar Marca', 500);
            }

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Marca cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateMarca(object $atributes): object
    {
        try {
            $record = Marca::where('id', $atributes->id)->first();

            if (!$record) {
                throw new Exception('Marca não encontrada', 404);
            }

            $record->fill(get_object_vars($atributes));
            $saved = $record->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar Marca', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Marca alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteMarca(int|string $id): object
    {
        try {
            $record = Marca::where('id', $id)->first();

            if (!$record) {
                throw new Exception('Marca não encontrada', 404);
            }

            $saved = $record->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir Marca', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Marca excluída com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getMarcaPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.descricao',
            'ent.created_at',
        );

        $query->from('marcas as ent');
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

    public function getMarcaId(int|string $id): array
    {
        try {
            $query = DB::table('marcas as ent')
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

    public function getMarcaAsync(object $params): array
    {
        $query = DB::table('marcas as ent')
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
