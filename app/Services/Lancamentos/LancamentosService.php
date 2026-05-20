<?php

namespace App\Services\Lancamentos;

use App\Models\Banco;
use App\Models\ContaBancaria;
use App\Models\Lancamento;
use App\Models\Lancamentos;
use App\Models\RecorrenciaStatus;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class LancamentosService
{


    public function handleLookupsLancamentos()
    {
        $data = [];
        // $data['contas_bancarias'] = ContaBancaria::all()->orderBy('apelido');
        $data['contas_bancarias'] = ContaBancaria::orderBy('apelido')->get();
        return $data;
    }

    public function handleAddLancamentos(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Lancamentoss = $this->createLancamentos($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditLancamentos(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Lancamentoss = $this->updateLancamentos($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteLancamentos(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Lancamentoss = $this->deleteLancamentos($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateLancamentos(object $atributes)
    {
        try {
            $queryUpdate = Lancamento::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Lancamentos');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Lancamentos alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteLancamentos(int  $id)
    {
        try {
            $queryDelete = Lancamento::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Lancamentos');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Lancamentos deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createLancamentos(object $atributes)
    {
        try {
            $atributes->status_job_id = 1;
            $newData = new Lancamento((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a Lancamentos');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Lancamentos cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getLancamentosPaginate($atributes)
    {

        $query = DB::query();
        
        $query->select(
            'lanc.id',
            'lanc.id AS lancamento_id',
            'lanc.tipo_lancamento',
            'lanc.despesa_id',
            'lanc.receita_id',
            'lanc.contas_bancaria_origem_id',
            'lanc.contas_bancaria_destino_id',
            'lanc.qtd_total_parcela',
            'lanc.valor',
            'lanc.valor_parcela',
            'lanc.acrescimo',
            'lanc.desconto',
            'lanc.subtotal',
            'lanc.total',
            'lanc.ano_referencia',
            'lanc.dthr_lancamento',
            DB::raw("DATE_FORMAT(lanc.dthr_lancamento, '%d/%m/%Y') as dthr_lancamento_format"),
            'lanc.descricao',
            'dp.nome AS nome_despesa',
            'rec.descricao AS nome_receita',
            'contas_b_orig.apelido AS banco_origem',
            'contas_b_dest.apelido AS banco_destino',
            's_jobs.descricao AS status ',
            

        );

        $query->from('lancamentos as lanc');
        $query->leftJoin('despesas as dp', 'dp.id', '=', 'lanc.despesa_id');
        $query->leftJoin('receitas as rec', 'rec.id', '=', 'lanc.receita_id');
        
        $query->leftJoin('contas_bancarias as contas_b_orig', 'contas_b_orig.id', '=', 'lanc.contas_bancaria_origem_id');
        $query->leftJoin('contas_bancarias as contas_b_dest', 'contas_b_dest.id', '=', 'lanc.contas_bancaria_destino_id');

        $query->leftJoin('status_jobs as s_jobs', 's_jobs.id', '=', 'lanc.status_job_id');
        $query->whereNull('lanc.deleted_at');
        $query->orderBy('lanc.dthr_lancamento');

        if (isset($atributes->tipo_lancamento)) {
            $query->where('lanc.tipo_lancamento', $atributes->tipo_lancamento);
        }
        
        if (isset($atributes->despesa_id) && isset($atributes->despesa_id)) {
            $query->where('lanc.despesa_id', $atributes->despesa_id);
        }

        if (isset($atributes->receita_id) && !empty($atributes->receita_id)) {
            $query->where('lanc.receita_id', $atributes->receita_id);
        }
        
        if (isset($atributes->dthr_lancamento) && !empty($atributes->dthr_lancamento)) {
            $data = \DateTime::createFromFormat('Y-m-d', $atributes->dthr_lancamento);
            if ($data) {
                $query->whereBetween('lanc.dthr_lancamento', [
                    $data->format('Y-m-d') . ' 00:00:00',
                    $data->format('Y-m-d') . ' 23:59:59'
                ]);
            }
        }
        
        if (isset($atributes->contas_bancaria_origem_id) && !empty($atributes->contas_bancaria_origem_id)) {
            $query->where('lanc.contas_bancaria_origem_id', $atributes->contas_bancaria_origem_id);
        }

        
        if (isset($atributes->contas_bancaria_destino_id) && !empty($atributes->contas_bancaria_destino_id)) {
            $query->where('lanc.contas_bancaria_destino_id', $atributes->contas_bancaria_destino_id);
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('dp.nome', 'like', '%' . $chave . '%')
                    // ->orWhere('dp.data_vencimento', 'like', '%' . $chave . '%')
        
                ;
            });
        }


        $paginate = new PaginateService;
        $lancamentos = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $lancamentos->appends((array) $atributes);
        $lancamentos = collect($lancamentos)->toArray();
        return $lancamentos;
    }
    public function getLancamentossAsync($params)
    {

        $query = DB::table('lancamentos as lanc')
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

    public function getLancamentosId(int $id_lancamento) {
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
            $query->from('lancamentos as lanc');
            $query->leftJoin('despesas as dp', 'dp.id', '=', 'lanc.despesa_id');
            $query->leftJoin('status_jobs as s_jobs', 's_jobs.id', '=', 'lanc.status_job_id');
            $query->whereNull('lanc.deleted_at');
            $query->where('lanc.id', $id_lancamento);

            $Lancamentos = collect($query->first())->toArray();

            return $Lancamentos;

        } catch (Exception $e) {
            throw $e;
        }
    }
}
