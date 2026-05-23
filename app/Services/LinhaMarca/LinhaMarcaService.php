<?php

namespace App\Services\LinhaMarca;

use App\Models\LinhaMarca;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class LinhaMarcaService
{
    public function __construct()
    {
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsLinhaMarca(): array
    {
        $data = [];
        return $data;
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddLinhaMarca(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result              = (object) [];
            $result->linhaMarca = $this->createLinhaMarca($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditLinhaMarca(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result              = (object) [];
            $result->linhaMarca = $this->updateLinhaMarca($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteLinhaMarca(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result              = (object) [];
            $result->linhaMarca = $this->deleteLinhaMarca($id);

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

    public function createLinhaMarca(object $atributes): object
    {
        try {
            $newData = new LinhaMarca((array) $atributes);
            $saved   = $newData->save();

            if (!$saved) {
                throw new Exception('Não foi possível cadastrar Linha de Marca', 500);
            }

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Linha de Marca cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updateLinhaMarca(object $atributes): object
    {
        try {
            $record = LinhaMarca::where('id', $atributes->id)->first();

            if (!$record) {
                throw new Exception('Linha de Marca não encontrada', 404);
            }

            $record->fill(get_object_vars($atributes));
            $saved = $record->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar Linha de Marca', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Linha de Marca alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteLinhaMarca(int|string $id): object
    {
        try {
            $record = LinhaMarca::where('id', $id)->first();

            if (!$record) {
                throw new Exception('Linha de Marca não encontrada', 404);
            }

            $saved = $record->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir Linha de Marca', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Linha de Marca excluída com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getLinhaMarcaPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.descricao',
            'ent.created_at',
        );

        $query->from('linhas_marcas as ent');
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

    public function getLinhaMarcaId(int|string $id): array
    {
        try {
            $query = DB::table('linhas_marcas as ent')
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

    public function getLinhaMarcaAsync(object $params): array
    {
        $query = DB::table('linhas_marcas as ent')
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
