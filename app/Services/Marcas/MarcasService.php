<?php

namespace App\Services\Marcas;

use App\Models\Banco;
use App\Models\ContaBancaria;
use App\Models\Receita;
use App\Services\Lancamentos\LancamentosService;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class MarcasService
{

    protected $_service_lancamentos;
    public function __construct()
    {
        $this->_service_lancamentos = new LancamentosService();
    }

    public function handleLookupsMarcas()
    {
        $data = [];
        $data['bancos'] = Banco::all();
        return $data;
    }

    public function handleAddMarcas(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Marcas = $this->createMarcas($atributes);
            if ($result->Marcas && !empty($atributes->saldo)) {

                $params = (object)[];

                $params->receita_id = 1;
                $params->contas_bancaria_destino_id = $result->Marcas->data->id;
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

    public function handleEditMarcas(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Marcas = $this->updateMarcas($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteMarcas(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Marcas = $this->deleteMarcas($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateMarcas(object $atributes)
    {
        try {
            $queryUpdate = ContaBancaria::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Marcas');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Marcas alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteMarcas(int  $id)
    {
        try {
            $queryDelete = ContaBancaria::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Marcas');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Marcas deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createMarcas(object $atributes)
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


    public function getMarcasPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'pl.id',
            'pl.marca_id',
            'pl.uso_periodo_id',
            'pl.nome',
            'pl.codigo',
            'pl.descricao',
            'pl.tipo_id',
            'pl.hora_protecao',

        );

        $query->from('produto_linhas as pl');
        // $query->leftJoin('bancos as bco', 'bco.id', '=', 'pl.banco_id');
        $query->whereNull('pl.deleted_at');
        $query->orderBy('pl.nome');

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
                $query->orWhere('pl.apelido', 'like', '%' . $chave . '%')
                    // ->orWhere('dp.data_vencimento', 'like', '%' . $chave . '%')

                ;
            });
        }


        $paginate = new PaginateService;
        $Marcas = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $Marcas->appends((array) $atributes);
        $Marcas = collect($Marcas)->toArray();
        return $Marcas;
    }
    public function getMarcasAsync($params)
    {

        $query = DB::table('marcas as m')
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

    public function getMarcasId(int $id_ContasBancaria)
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

            $Marcas = collect($query->first())->toArray();

            return $Marcas;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
