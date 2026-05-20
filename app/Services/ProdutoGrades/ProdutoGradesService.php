<?php

namespace App\Services\ProdutoGrades;

use App\Models\ContaBancaria;
use App\Models\Marca;
use App\Models\ProdutoGrade;
use App\Models\Tamanho;
use App\Models\TipoProduto;
use App\Models\UnidadeTipo;
use App\Models\UsoPeriodo;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ProdutoGradesService
{

    public function listarLookupsProdutoGrades()
    {
        $data = [];
        $data['tamanhos'] = Tamanho::all();
        $data['unidades'] = UnidadeTipo::orderBy('descricao')->get();
        return $data;
    }

    public function handleAddProdutoGrades(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->produtoGrades = $this->createProdutoGrades($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditProdutoGrades(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->produtoGrades = $this->updateProdutoGrades($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteProdutoGrades(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->produtoGrades = $this->deleteProdutoGrades($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateProdutoGrades(object $atributes)
    {
        try {
            $queryUpdate = ProdutoGrade::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Grade do Produto');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Grade do Produto alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteProdutoGrades(int  $id)
    {
        try {
            $queryDelete = ProdutoGrade::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Grade do Produto');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Grade do Produto deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createProdutoGrades(object $atributes)
    {
        try {
            $atributes->status_job_id = 1;
            $newData = new ProdutoGrade((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a Grade do Produto');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Grade do Produto cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getProdutoGradesPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'pl.id',
            'pl.marca_id',
            'm.nome AS marca_nome',
            'm.codigo AS marca_codigo',
            'pl.uso_periodo_id',
            'up.descricao AS uso_periodo_descricao',
            'pl.nome',
            'pl.codigo',
            'pl.descricao',
            'pl.tipo_id',
            'tp.descricao AS tipo_descricao',
            'pl.hora_protecao',

        );

        $query->from('produto_Grades as pl');
        $query->leftJoin('marcas as m', 'm.id', '=', 'pl.marca_id');
        $query->leftJoin('uso_periodos as up', 'up.id', '=', 'pl.uso_periodo_id');
        $query->leftJoin('tipo_produtos as tp', 'tp.id', '=', 'pl.tipo_id');
        $query->whereNull('pl.deleted_at');
        $query->orderBy('pl.nome');

        if (isset($atributes->marca_id)) {
            $query->where('pl.marca_id', $atributes->marca_id);
        }

        if (isset($atributes->Grade_id) && isset($atributes->Grade_id)) {
            $query->where('pl.id', $atributes->Grade_id);
        }

        if (isset($atributes->uso_periodo_id) && !empty($atributes->uso_periodo_id)) {
            $query->where('pl.uso_periodo_id', $atributes->uso_periodo_id);
        }

        if (!empty($atributes->hora_protecao)) {
            $chave = $atributes->hora_protecao;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('pl.hora_protecao', 'like', '%' . $chave . '%')

                ;
            });
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('pl.nome', 'like', '%' . $chave . '%')
                    ->orWhere('pl.codigo', 'like', '%' . $chave . '%')
                    ->orWhere('m.nome', 'like', '%' . $chave . '%')
                    ->orWhere('m.codigo', 'like', '%' . $chave . '%')
                    ->orWhere('up.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('tp.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('pl.hora_protecao', 'like', '%' . $chave . '%')

                ;
            });
        }


        $paginate = new PaginateService;
        $ProdutoGrades = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $ProdutoGrades->appends((array) $atributes);
        $ProdutoGrades = collect($ProdutoGrades)->toArray();
        return $ProdutoGrades;
    }
    public function getProdutoGradessAsync($params)
    {

        $query = DB::table('produto_Grades as pl')
        ->leftJoin('marcas as m', 'm.id', '=', 'pl.marca_id')
            ->whereNull('pl.deleted_at')
            ->select(
                'pl.id',
                'pl.nome',
                'pl.codigo',
                'm.nome AS marca_nome',
                'm.codigo AS marca_codigo',
            );

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('pl.nome', 'like', '%' . $chave . '%')
                ->orWhere('pl.codigo', 'like', '%' . $chave . '%')
                ->orWhere('m.nome', 'like', '%' . $chave . '%')
                ;
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getProdutoGradesId(int $id_ContasBancaria)
    {
        try {
            $query = DB::query();
            $query->select(
                'dp.id',
                'lanc.id',
                'lanc.qtd_total_parcela',
                'lanc.valor',
                'lanc.valor_parcela',
                'lanc.acrescimo',
                'lanc.desconto',
                'lanc.subtotal',
                'lanc.total',
                'lanc.ano_referencia',
                'dp.nome AS nome_despesa',
                's_jobs.descricao AS status ',
            );
            $query->from('produto_Grades as pl');
            $query->leftJoin('despesas as dp', 'dp.id', '=', 'lanc.despesa_id');
            $query->leftJoin('status_jobs as s_jobs', 's_jobs.id', '=', 'lanc.status_job_id');
            $query->whereNull('lanc.deleted_at');
            $query->where('lanc.id', $id_ContasBancaria);

            $ProdutoGrades = collect($query->first())->toArray();

            return $ProdutoGrades;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
