<?php

namespace App\Services\SubtipoProduto;

use App\Models\Banco;
use App\Models\Receita;
use App\Models\SubtipoProduto;
use App\Models\TipoProduto;
use App\Services\Lancamentos\LancamentosService;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class SubtipoProdutoService
{

    // protected $_service_lancamentos;
    public function __construct()
    {
        // $this->_service_lancamentos = new LancamentosService();
    }

    public function handleLookupsSubtipoProduto()
    {
        $data = [];
        $data['tipo_produtos'] = TipoProduto::where('ativo', true)->orderBy('nome')->get();

        return $data;
    }

    public function handleAddSubtipoProduto(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->SubtipoProduto = $this->createSubtipoProduto($atributes);


            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditSubtipoProduto(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->SubtipoProduto = $this->updateSubtipoProduto($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteSubtipoProduto(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->SubtipoProduto = $this->deleteSubtipoProduto($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateSubtipoProduto(object $atributes)
    {
        try {
            $queryUpdate = SubtipoProduto::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Tipo de Produto');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Tipo de Produto alterado com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteSubtipoProduto(int  $id)
    {
        try {
            $queryDelete = SubtipoProduto::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Tipo de Produto');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Tipo de Produto excluido com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createSubtipoProduto(object $atributes)
    {
        try {
            // $atributes->status_job_id = 1;
            $newData = new SubtipoProduto((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar o Tipo de Produto');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Tipo de Produto cadastrado com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getSubtipoProdutoPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'stp.id',
            'tp.nome AS tipo_produtos_nome',
            'tp.id AS tipo_produtos_id',
            'stp.nome',
            'stp.ativo',

        );

        $query->from('subtipo_produtos as stp');
        $query->join('tipo_produtos as tp', 'tp.id', '=', 'stp.tipo_produtos_id');
        $query->whereNull('stp.deleted_at');
        $query->whereNull('tp.deleted_at');
        $query->orderBy('tp.nome');
        $query->orderBy('stp.nome');

        // if (isset($atributes->tipo_ContasBancaria)) {
        //     $query->where('lanc.tipo_ContasBancaria', $atributes->tipo_ContasBancaria);
        // }

        // if (isset($atributes->despesa_id) && isset($atributes->despesa_id)) {
        //     $query->where('lanc.despesa_id', $atributes->despesa_id);
        // }

        // if (isset($atributes->receita_id) && !empty($atributes->receita_id)) {
        //     $query->where('lanc.receita_id', $atributes->receita_id);
        // }

        // if (isset($atributes->dthr_ContasBancaria) && !empty($atributes->dthr_ContasBancaria)) {
        //     $data = \DateTime::createFromFormat('Y-m-d', $atributes->dthr_ContasBancaria);
        //     if ($data) {
        //         $query->whereBetween('lanc.dthr_ContasBancaria', [
        //             $data->format('Y-m-d') . ' 00:00:00',
        //             $data->format('Y-m-d') . ' 23:59:59'
        //         ]);
        //     }
        // }

        // if (isset($atributes->contas_bancaria_origem_id) && !empty($atributes->contas_bancaria_origem_id)) {
        //     $query->where('lanc.contas_bancaria_origem_id', $atributes->contas_bancaria_origem_id);
        // }


        // if (isset($atributes->contas_bancaria_destino_id) && !empty($atributes->contas_bancaria_destino_id)) {
        //     $query->where('lanc.contas_bancaria_destino_id', $atributes->contas_bancaria_destino_id);
        // }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('tp.nome', 'like', '%' . $chave . '%');
                $query->orWhere('stp.nome', 'like', '%' . $chave . '%');
                    // ->orWhere('dp.data_vencimento', 'like', '%' . $chave . '%')

                ;
            });
        }


        $paginate = new PaginateService;
        $SubtipoProduto = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $SubtipoProduto->appends((array) $atributes);
        $SubtipoProduto = collect($SubtipoProduto)->toArray();
        return $SubtipoProduto;
    }
    public function getSubtipoProdutoAsync($params)
    {

        $query = DB::table('SubtipoProduto as m')
            ->whereNull('m.deleted_at')
            ->select(
                'm.id',
                'm.nome',
                'm.codigo'
            );

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('m.nome', 'like', '%' . $chave . '%');
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getSubtipoProdutoId(int $id_ContasBancaria)
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
            $query->from('produto_linhas as pl');
            $query->leftJoin('despesas as dp', 'dp.id', '=', 'lanc.despesa_id');
            $query->leftJoin('status_jobs as s_jobs', 's_jobs.id', '=', 'lanc.status_job_id');
            $query->whereNull('lanc.deleted_at');
            $query->where('lanc.id', $id_ContasBancaria);

            $SubtipoProduto = collect($query->first())->toArray();

            return $SubtipoProduto;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
