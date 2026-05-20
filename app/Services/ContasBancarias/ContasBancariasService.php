<?php

namespace App\Services\ContasBancarias;

use App\Models\Banco;
use App\Models\ContaBancaria;
use App\Models\Receita;
use App\Services\Lancamentos\LancamentosService;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ContasBancariasService
{

    protected $_service_lancamentos;
    public function __construct()
    {
        $this->_service_lancamentos = new LancamentosService();
    }

    public function handleLookupsContasBancarias()
    {
        $data = [];
        $data['bancos'] = Banco::all();
        return $data;
    }

    public function handleAddContasBancarias(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->contasBancarias = $this->createContasBancarias($atributes);
            if($result->contasBancarias && !empty($atributes->saldo)){
                
                $params = (object)[];

                $params->receita_id = 1;
                $params->contas_bancaria_destino_id = $result->contasBancarias->data->id;
                $params->tipo_lancamento = false;
                $params->valor = $atributes->saldo;
                $params->dthr_lancamento = Carbon::now()->format('Y-m-d');
                $params->descricao = "Saldo inicial conta bancária: {$atributes->apelido}";
                $this->_service_lancamentos->handleAddLancamentos($params);
            }

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditContasBancarias(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->ContasBancariass = $this->updateContasBancarias($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteContasBancarias(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->ContasBancariass = $this->deleteContasBancarias($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateContasBancarias(object $atributes)
    {
        try {
            $queryUpdate = ContaBancaria::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o ContasBancarias');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'ContasBancarias alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteContasBancarias(int  $id)
    {
        try {
            $queryDelete = ContaBancaria::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a ContasBancarias');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'ContasBancarias deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createContasBancarias(object $atributes)
    {
        try {
            $atributes->status_job_id = 1;
            $newData = new ContaBancaria((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a Contas Bancarias');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Contas Bancarias cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getContasBancariasPaginate($atributes)
    {

        $query = DB::query();
        
        $query->select(
            'cb.id',
            'cb.id AS contas_bancaria_id',
            'cb.banco_id',
            'cb.conta_pj',
            'cb.apelido',
            'cb.saldo',
            'cb.ativo',
            'bco.codigo AS banco_codigo',            
            'bco.nome AS banco_nome' ,           

        );

        $query->from('contas_bancarias as cb');
        $query->leftJoin('bancos as bco', 'bco.id', '=', 'cb.banco_id');
        $query->whereNull('cb.deleted_at');
        $query->orderBy('cb.apelido');

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
                $query->orWhere('cb.apelido', 'like', '%' . $chave . '%')
                    // ->orWhere('dp.data_vencimento', 'like', '%' . $chave . '%')
        
                ;
            });
        }


        $paginate = new PaginateService;
        $ContasBancarias = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $ContasBancarias->appends((array) $atributes);
        $ContasBancarias = collect($ContasBancarias)->toArray();
        return $ContasBancarias;
    }
    public function getContasBancariassAsync($params)
    {

        $query = DB::table('contas_bancarias as cb')
            ->whereNull('dp.deleted_at')
            ->select('dp.*');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('dp.nome', 'like', '%' . $chave . '%');
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getContasBancariasId(int $id_ContasBancaria) {
        try {
            $query = DB::query();
            $query->select('dp.id',
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
            $query->from('contas_bancarias as cb');
            $query->leftJoin('despesas as dp', 'dp.id', '=', 'lanc.despesa_id');
            $query->leftJoin('status_jobs as s_jobs', 's_jobs.id', '=', 'lanc.status_job_id');
            $query->whereNull('lanc.deleted_at');
            $query->where('lanc.id', $id_ContasBancaria);

            $ContasBancarias = collect($query->first())->toArray();

            return $ContasBancarias;

        } catch (Exception $e) {
            throw $e;
        }
    }
}
