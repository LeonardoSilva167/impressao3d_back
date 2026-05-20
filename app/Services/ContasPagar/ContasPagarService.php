<?php

namespace App\Services\ContasPagar;

use App\Models\ContasApagar;
use App\Models\ContasPagar;
use App\Models\RecorrenciaStatus;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class ContasPagarService
{

    
    public function handleLookupsContasPagar()
    {
        $data = [];
        $data['recorrencias'] = RecorrenciaStatus::all();
        return $data;
    }

    public function handleAddContasPagar(object $atributes){

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->ContasPagar = $this->createContasPagar($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditContasPagar(object $atributes){

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->ContasPagar = $this->updateContasPagar($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteContasPagar(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->ContasPagar = $this->deleteContasPagar($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateContasPagar(object $atributes)
    {
        try {
            $queryUpdate = ContasPagar::where('id', $atributes->id)->first();            
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o ContasPagar');
            } 

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'ContasPagar alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteContasPagar(int  $id)
    {
        try {
            $queryDelete = ContasPagar::where('id', $id)->first();            
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a ContasPagar');
            } 

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'ContasPagar deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createContasPagar(object $atributes)
    {
        try {
            $atributes->qtd_parcela = !empty($atributes->qtd_parcela)?$atributes->qtd_parcela: null;  

            $newData = new ContasPagar((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a ContasPagar');
            } 
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'ContasPagar cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getContasPagarPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'cp.id',
            'cp.data_vencimento',
            DB::raw("DATE_FORMAT(cp.data_vencimento, '%d/%m/%Y') as data_vencimento_format"),
        );

        $query->from('contas_apagar as cp');
        // $query->leftJoin('recorrencia_status as rec', 'rec.id', '=', 'dp.recorrencia_status_id');
        $query->whereNull('cp.deleted_at');
        $query->orderBy('cp.data_vencimento');

        if (isset($atributes->recorrencia_status_id) && !empty($atributes->recorrencia_status_id)) {
            // $query->where('dp.recorrencia_status_id', $atributes->recorrencia_status_id);
        }

        
        // if (!empty($atributes->palavra_chave)) {
        //     $chave = $atributes->palavra_chave;
        //     $query->where(function ($query) use ($chave) {
        //         $query->orWhere('dp.nome', 'like', '%' . $chave . '%')
        //             ->orWhere('dp.data_vencimento', 'like', '%' . $chave . '%')
        //             ->orWhere('dp.dia_vencimento', 'like', '%' . $chave . '%')
        //             ->orWhere('dp.mes_vencimento', 'like', '%' . $chave . '%')
        //             ->orWhere('dp.qtd_parcela', 'like', '%' . $chave . '%')
        //             ;
        //     });
        // }


        $paginate = new PaginateService;
        $ContasPagar = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $ContasPagar->appends((array) $atributes);

        $ContasPagar = collect($ContasPagar)->toArray();
        return $ContasPagar;
    }

}