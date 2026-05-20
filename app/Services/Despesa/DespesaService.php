<?php

namespace App\Services\Despesa;

use App\Models\Despesa;
use App\Models\RecorrenciaStatus;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class DespesaService
{


    public function handleLookupsDespesas()
    {
        $data = [];
        $data['recorrencias'] = RecorrenciaStatus::all();
        return $data;
    }

    public function handleAddDespesa(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->despesas = $this->createDespesa($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditDespesa(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->despesas = $this->updateDespesa($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteDespesa(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->despesas = $this->deleteDespesa($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateDespesa(object $atributes)
    {
        try {
            $queryUpdate = Despesa::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o despesa');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Despesa alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteDespesa(int  $id)
    {
        try {
            $queryDelete = Despesa::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a despesa');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Despesa deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createDespesa(object $atributes)
    {
        try {
            $atributes->qtd_parcela = !empty($atributes->qtd_parcela) ? $atributes->qtd_parcela : '1';

            $newData = new Despesa((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a despesa');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Despesa cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getDespesasPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'dp.id',
            'dp.nome',
            'dp.valor',
            'dp.qtd_parcela',
            'dp.valor_parcela',
            'dp.data_vencimento',
            DB::raw('CAST(dp.dia_vencimento AS UNSIGNED) as dia_vencimento'),
            DB::raw('CAST(dp.mes_vencimento AS UNSIGNED) as mes_vencimento'),
            DB::raw("DATE_FORMAT(data_vencimento, '%d') as data_vencimento_format_dia"),
            DB::raw("DATE_FORMAT(data_vencimento, '%d/%m/%Y') as data_vencimento_format"),
            'dp.ativo',
            'dp.recorrencia_status_id',
            'rec.descricao AS recorrencia',
        );

        $query->from('despesas as dp');
        $query->leftJoin('recorrencia_status as rec', 'rec.id', '=', 'dp.recorrencia_status_id');
        $query->whereNull('dp.deleted_at');
        $query->orderBy('dp.nome');

        if (isset($atributes->recorrencia_status_id) && !empty($atributes->recorrencia_status_id)) {
            $query->where('dp.recorrencia_status_id', $atributes->recorrencia_status_id);
        }


        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('dp.nome', 'like', '%' . $chave . '%')
                    ->orWhere('dp.data_vencimento', 'like', '%' . $chave . '%')
                    ->orWhere('dp.dia_vencimento', 'like', '%' . $chave . '%')
                    ->orWhere('dp.mes_vencimento', 'like', '%' . $chave . '%')
                    ->orWhere('dp.qtd_parcela', 'like', '%' . $chave . '%')
                ;
            });
        }


        $paginate = new PaginateService;
        $despesas = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $despesas->appends((array) $atributes);

        $despesas = collect($despesas)->toArray();
        return $despesas;
    }
    public function getDespesasAsync($params)
    {

        $query = DB::table('despesas as dp')
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

    public function getDespesaId(int $id_despesa) {
        try {
            $query = DB::query();
            $query->select('dp.id',
            'dp.id',
            'dp.nome',
            'dp.valor',
            'dp.qtd_parcela',
            'dp.valor_parcela',
            'dp.ativo',
            'dp.recorrencia_status_id',
            'rec.descricao AS recorrencia',
            );
            $query->from('despesas as dp');
            $query->leftJoin('recorrencia_status as rec', 'rec.id', '=', 'dp.recorrencia_status_id');
            $query->whereNull('dp.deleted_at');
            $query->where('dp.id', $id_despesa);

            $despesa = collect($query->first())->toArray();

            return $despesa;

        } catch (Exception $e) {
            throw $e;
        }
    }
}
