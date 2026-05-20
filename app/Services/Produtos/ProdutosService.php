<?php

namespace App\Services\Produtos;

use App\Models\ContaBancaria;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\Tamanho;
use App\Models\TipoProduto;
use App\Models\UsoPeriodo;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ProdutosService
{

    public function listarLookupsProdutos()
    {
        $data = [];
        return $data;
    }

    public function handleAddProdutos(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->produtos = $this->createProdutos($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditProdutos(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->produtos = $this->updateProdutos($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteProdutos(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->produtos = $this->deleteProdutos($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateProdutos(object $atributes)
    {
        try {
            $queryUpdate = TabProduto::where('codigo_base', $atributes->codigo_base)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Produto');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Produto alterado com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteProdutos(int  $codigo_base)
    {
        try {
            $queryDelete = Produto::where('codigo_base', $codigo_base)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir o Produto');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Produto deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createProdutos(object $atributes)
    {
        try {            
            $newData = new Produto((array)($atributes));
            $saved = $newData->save();
            
            if (!$saved) {
                throw new Exception('Não foi possível adicionar o Produto');
            }
        
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Produto cadastrado com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getProdutosPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'p.codigo_base',
            'p.descricao',           

        );

        $query->from('produtos as p');
        $query->whereNull('p.deleted_at');
        $query->orderBy('p.descricao');

           if (!empty($atributes->descricao)) {
            $chave = $atributes->descricao;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('p.codigo_base', 'like', '%' . $chave . '%')
                    ->orWhere('p.descricao', 'like', '%' . $chave . '%')

                ;
            });
        }
        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('p.codigo_base', 'like', '%' . $chave . '%')
                    ->orWhere('p.descricao', 'like', '%' . $chave . '%')

                ;
            });
        }


        $paginate = new PaginateService;
        $Produtos = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $Produtos->appends((array) $atributes);
        $Produtos = collect($Produtos)->toArray();
        return $Produtos;
    }
    public function getProdutossAsync($params)
    {

        $query = DB::table('produtos as p')
            ->whereNull('p.deleted_at')
            ->select(
                'p.codigo_base',
                'p.descricao',
            );

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('p.codigo_base', 'like', '%' . $chave . '%')
                ->orWhere('p.descricao', 'like', '%' . $chave . '%');
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getProdutosId(int $id_ContasBancaria)
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

            $Produtos = collect($query->first())->toArray();

            return $Produtos;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
