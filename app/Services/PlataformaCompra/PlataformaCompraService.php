<?php

namespace App\Services\PlataformaCompra;

use App\Models\PlataformaCompra;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class PlataformaCompraService
{
    public function __construct()
    {
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsPlataformaCompra(): array
    {
        $data = [];
        return $data;
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddPlataformaCompra(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                     = (object) [];
            $result->plataformaCompra = $this->createPlataformaCompra($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditPlataformaCompra(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                     = (object) [];
            $result->plataformaCompra = $this->updatePlataformaCompra($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeletePlataformaCompra(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                     = (object) [];
            $result->plataformaCompra = $this->deletePlataformaCompra($id);

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

    public function createPlataformaCompra(object $atributes): object
    {
        try {
            $newData = new PlataformaCompra((array) $atributes);
            $saved   = $newData->save();

            if (!$saved) {
                throw new Exception('Não foi possível cadastrar Plataforma de Compra', 500);
            }

            return (object) [
                'data'    => $newData,
                'status'  => true,
                'message' => 'Plataforma de Compra cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function updatePlataformaCompra(object $atributes): object
    {
        try {
            $record = PlataformaCompra::where('id', $atributes->id)->first();

            if (!$record) {
                throw new Exception('Plataforma de Compra não encontrada', 404);
            }

            $record->fill(get_object_vars($atributes));
            $saved = $record->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar Plataforma de Compra', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Plataforma de Compra alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deletePlataformaCompra(int|string $id): object
    {
        try {
            $record = PlataformaCompra::where('id', $id)->first();

            if (!$record) {
                throw new Exception('Plataforma de Compra não encontrada', 404);
            }

            $saved = $record->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir Plataforma de Compra', 500);
            }

            return (object) [
                'data'    => [],
                'status'  => true,
                'message' => 'Plataforma de Compra excluída com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getPlataformaCompraPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.descricao',
            'ent.url',
            'ent.created_at',
        );

        $query->from('plataforma_compras as ent');
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
                  ->orWhere('ent.url', 'like', '%' . $chave . '%');
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

    public function getPlataformaCompraId(int|string $id): array
    {
        try {
            $query = DB::table('plataforma_compras as ent')
                ->select(
                    'ent.id',
                    'ent.descricao',
                    'ent.url',
                    'ent.created_at',
                )
                ->whereNull('ent.deleted_at')
                ->where('ent.id', $id);

            return collect($query->first())->toArray();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getPlataformaCompraAsync(object $params): array
    {
        $query = DB::table('plataforma_compras as ent')
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
