<?php

namespace App\Services\Receitas;

use App\Models\Receita;
use App\Models\Receitas;
use App\Models\RecorrenciaStatus;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class ReceitasService
{


    public function handleLookupsReceitas()
    {
        $data = [];
        return $data;
    }

    public function handleAddReceitas(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Receitass = $this->createReceitas($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditReceitas(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Receitass = $this->updateReceitas($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteReceitas(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Receitass = $this->deleteReceitas($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateReceitas(object $atributes)
    {
        try {
            $queryUpdate = Receita::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Receitas');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Receitas alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteReceitas(int  $id)
    {
        try {
            $queryDelete = Receita::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Receitas');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Receitas deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createReceitas(object $atributes)
    {
        try {
            $atributes->qtd_parcela = !empty($atributes->qtd_parcela) ? $atributes->qtd_parcela : '1';

            $newData = new Receita((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a Receitas');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Receitas cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getReceitasPaginate($atributes)
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

        $query->from('receitas as dp');
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
        $Receitass = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $Receitass->appends((array) $atributes);

        $Receitass = collect($Receitass)->toArray();
        return $Receitass;
    }
    public function getReceitasAsync($params)
    {

        $query = DB::table('receitas as rec')
            ->whereNull('rec.deleted_at')
            ->select('rec.*');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('rec.descricao', 'like', '%' . $chave . '%');
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getReceitasId(int $id_Receitas) {
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
            $query->from('receitas as dp');
            $query->leftJoin('recorrencia_status as rec', 'rec.id', '=', 'dp.recorrencia_status_id');
            $query->whereNull('dp.deleted_at');
            $query->where('dp.id', $id_Receitas);

            $Receitas = collect($query->first())->toArray();

            return $Receitas;

        } catch (Exception $e) {
            throw $e;
        }
    }
}
