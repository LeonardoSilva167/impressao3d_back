<?php

namespace App\Services\Clientes;

use App\Models\Cliente;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ClientesService
{

    // protected $_service_lancamentos;
    public function __construct()
    {
        // $this->_service_lancamentos = new LancamentosService();
    }

    public function handleLookupsClientes()
    {
        $data = [];
        // $data['bancos'] = Banco::all();
        // $data['bancos'] = Banco::all();
        return $data;
    }

    public function handleAddClientes(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->clientes = $this->createClientes($atributes);
            if($result->clientes && !empty($atributes->saldo)){
                
                $params = (object)[];

                $params->receita_id = 1;
                $params->contas_bancaria_destino_id = $result->clientes->data->id;
                $params->tipo_lancamento = false;
                $params->valor = $atributes->saldo;
                $params->dthr_lancamento = Carbon::now()->format('Y-m-d');
                $params->descricao = "Saldo inicial conta bancária: {$atributes->apelido}";
                
            }

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditClientes(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->clientess = $this->updateClientes($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteClientes(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->clientess = $this->deleteClientes($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateClientes(object $atributes)
    {
        try {
            $queryUpdate = Cliente::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Clientes');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Clientes alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteClientes(int  $id)
    {
        try {
            $queryDelete = Cliente::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Clientes');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Clientes deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createClientes(object $atributes)
    {
        try {
            $atributes->status_job_id = 1;
            $newData = new Cliente((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a Cliente');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Cliente cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getClientesPaginate($atributes)
    {

        $query = DB::query();
        
        $query->select(
            'cl.id',
            'cl.id AS cliente_id',
            'cl.codigo',
            'cl.nome',
            'cl.uf',
            'cl.cidade',

        );

        $query->from('clientes as cl');
        $query->whereNull('cl.deleted_at');
        $query->orderBy('cl.codigo');
        $query->orderBy('cl.nome');

        if (isset($atributes->cliente_id)) {
            $query->where('cl.id', $atributes->cliente_id);
        }
        
        if (isset($atributes->cidade) && isset($atributes->cidade)) {
            $chave = $atributes->cidade;
            $query->where(function ($query) use ($chave) {
                $query->Where('cl.cidade', 'like', '%' . $chave . '%')
                ;
            });
        }

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
        $Clientes = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $Clientes->appends((array) $atributes);
        $Clientes = collect($Clientes)->toArray();
        return $Clientes;
    }
    public function getClientessAsync($params)
    {

        $query = DB::table('clientes as cl')
            ->whereNull('cl.deleted_at')
            ->select('cl.id', 'cl.codigo', 'cl.nome');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('cl.nome', 'like', '%' . $chave . '%');
                $query->OrWhere('cl.codigo', 'like', '%' . $chave . '%');
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getClientesId(int $id_ContasBancaria) {
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

            $Clientes = collect($query->first())->toArray();

            return $Clientes;

        } catch (Exception $e) {
            throw $e;
        }
    }
}
