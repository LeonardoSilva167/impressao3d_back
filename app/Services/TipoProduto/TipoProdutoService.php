<?php

namespace App\Services\TipoProduto;

use App\Models\Banco;
use App\Models\Receita;
use App\Models\TipoProduto;
use App\Services\Lancamentos\LancamentosService;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class TipoProdutoService
{

    // protected $_service_lancamentos;
    public function __construct()
    {
        // $this->_service_lancamentos = new LancamentosService();
    }

    public function handleLookupsTipoProduto()
    {
        $data = [];
        // $data['bancos'] = Banco::all();
        return $data;
    }

    public function handleAddTipoProduto(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->tipoProduto = $this->createTipoProduto($atributes);


            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditTipoProduto(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->tipoProduto = $this->updateTipoProduto($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteTipoProduto(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->tipoProduto = $this->deleteTipoProduto($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateTipoProduto(object $atributes)
    {
        try {
            $queryUpdate = TipoProduto::where('id', $atributes->id)->first();
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

    public function deleteTipoProduto(int  $id)
    {
        try {
            $queryDelete = TipoProduto::where('id', $id)->first();
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

    public function createTipoProduto(object $atributes)
    {
        try {
            // $atributes->status_job_id = 1;
            $newData = new TipoProduto((array)($atributes));
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


    public function getTipoProdutoPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'tp.id',
            'tp.nome',
            'tp.ativo',

        );

        $query->from('tipo_produtos as tp');
        // $query->leftJoin('bancos as bco', 'bco.id', '=', 'pl.banco_id');
        $query->whereNull('tp.deleted_at');
        $query->orderBy('tp.nome');

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
                $query->orWhere('tp.nome', 'like', '%' . $chave . '%')
                    // ->orWhere('dp.data_vencimento', 'like', '%' . $chave . '%')

                ;
            });
        }


        $paginate = new PaginateService;
        $TipoProduto = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $TipoProduto->appends((array) $atributes);
        $TipoProduto = collect($TipoProduto)->toArray();
        return $TipoProduto;
    }
    public function getTipoProdutoAsync($params)
    {

        $query = DB::table('TipoProduto as m')
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

    public function getTipoProdutoId(int $id_ContasBancaria)
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

            $TipoProduto = collect($query->first())->toArray();

            return $TipoProduto;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
