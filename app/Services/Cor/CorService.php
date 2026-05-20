<?php

namespace App\Services\Cor;

use App\Models\Cor;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class CorService
{
    public function __construct()
    {
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsCor(): array
    {
        $data = [];
        return $data;
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddCor(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result      = (object) [];
            $result->cor = $this->createCor($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditCor(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result      = (object) [];
            $result->cor = $this->updateCor($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteCor(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result      = (object) [];
            $result->cor = $this->deleteCor($id);

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

    public function createCor(object $atributes): object
    {
        try {
            $newData = new Cor((array) $atributes);
            $saved   = $newData->save();

            if (!$saved) {
                throw new Exception('Não foi possível cadastrar Cor', 500);
            }

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Cor cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateCor(object $atributes): object
    {
        try {
            $record = Cor::where('id', $atributes->id)->first();

            if (!$record) {
                throw new Exception('Cor não encontrada', 404);
            }

            $record->fill(get_object_vars($atributes));
            $saved = $record->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar Cor', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Cor alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteCor(int|string $id): object
    {
        try {
            $record = Cor::where('id', $id)->first();

            if (!$record) {
                throw new Exception('Cor não encontrada', 404);
            }

            $saved = $record->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir Cor', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Cor excluída com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getCorPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.descricao',
            'ent.codigo',
            'ent.hexadecimal',
            'ent.created_at',
        );

        $query->from('cores as ent');
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

    public function getCorId(int|string $id): array
    {
        try {
            $query = DB::table('cores as ent')
                ->select(
                    'ent.id',
                    'ent.descricao',
                    'ent.codigo',
                    'ent.hexadecimal',
                    'ent.created_at',
                )
                ->whereNull('ent.deleted_at')
                ->where('ent.id', $id);

            return collect($query->first())->toArray();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCorAsync(object $params): array
    {
        $query = DB::table('cores as ent')
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
