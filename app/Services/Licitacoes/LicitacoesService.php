<?php

namespace App\Services\Licitacoes;

use App\Models\Licitacoes;
use App\Models\Modalidade;
use App\Models\Orgao;
use App\Models\StatusClassificacao;
use App\Models\StatusCompra;
use App\Models\StatusLicitacao;
use App\Services\Orgao\OrgaoService;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class LicitacoesService
{

    protected $_service_orgaos;
    public function __construct()
    {
        $this->_service_orgaos = new OrgaoService();
    }

    public function handleLookupsLicitacoes()
    {
        $data = [];
        $data['modalidades'] = Modalidade::all();
        $data['statusLicitacoes'] = StatusLicitacao::all();
        $data['statusCompras'] = StatusCompra::all();
        return $data;
    }

    public function handleAddLicitacoes(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];

            if (empty($atributes->unidade_compradoras_id)) {
                $params = (object) [];

                $params->orgao_nome = $atributes->orgaos_nome;

                $params->unidadeCompradora = [
                    (object) [
                        'id' => null,
                        'orgaos_id' => null,
                        'codigo' => $atributes->unidade_compradoras_codigo,
                        'nome' => $atributes->unidade_compradoras_nome,
                        'cidade' => $atributes->unidade_compradoras_cidade,
                        'uf' => $atributes->unidade_compradoras_uf,
                    ]
                ];

                // return $this->_service_orgaos->handleAddOrgao($params);
                $result->orgaos = $this->_service_orgaos->handleAddOrgao($params);
                // return ($result->orgaos);
                if (empty($result->orgaos->status) || !$result->orgaos->status || empty($result->orgaos->data)) {
                    throw new Exception('Não foi possível cadastrar o órgão/unidade compradora.');
                } else {
                    $atributes->unidade_compradoras_id = $result->orgaos->data;
                }
            }

            $result->licitacoes = $this->createLicitacoes($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditLicitacoes(object $atributes)
    {

        try {

            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d\TH:i', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');
            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d H:i:s', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');


            DB::beginTransaction();
            $result = (object)[];
            $result->licitacoess = $this->updateLicitacoes($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteLicitacoes(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->licitacoess = $this->deleteLicitacoes($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateLicitacoes(object $atributes)
    {
        try {
            $queryUpdate = Licitacoes::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Licitacoes');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Licitacoes alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteLicitacoes(int  $id)
    {
        try {
            $queryDelete = Licitacoes::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Licitacoes');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Licitacoes deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createLicitacoes(object $atributes)
    {
        try {
            $newData = new Licitacoes((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a Licitacoes');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Licitacoes cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getLicitacoesPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'lc.id',
            'lc.id AS licitacao_id',
            'lc.num_compra',
            'lc.exercicio',
            'lc.data_limite_proposta',
            DB::raw("DATE_FORMAT(lc.data_limite_proposta, '%d/%m/%Y') as data_limite_proposta_format_date"),
            DB::raw("DATE_FORMAT(lc.data_limite_proposta, '%d/%m/%Y %H:%i:%s') as data_limite_proposta_format"),
            'lc.link_pcnp',
            'uc.id AS unidade_compradoras_id',
            'uc.codigo AS unidade_compradoras_codigo',
            'uc.nome AS unidade_compradoras_nome',
            'uc.uf AS unidade_compradoras_uf',
            'uc.cidade AS unidade_compradoras_cidade',
            'sl.id AS status_licitacoes_id',
            'sl.nome AS status_licitacao',
            'scomp.id AS status_compra_id',
            'scomp.nome AS status_compra',
            'scot.id AS status_cotacao_id',
            'scot.nome AS status_cotacao',
            'm.id AS modalidade_id',
            'm.nome AS modalidade_nome',

        );
        $query->from('licitacoes as lc');
        $query->leftJoin('unidade_compradoras as uc', 'uc.id', '=', 'lc.unidade_compradoras_id');
        $query->leftJoin('status_licitacoes as sl', 'sl.id', '=', 'lc.status_licitacoes_id');
        $query->leftJoin('status_compras as scomp', 'scomp.id', '=', 'lc.status_compra_id');
        $query->leftJoin('status_cotacoes as scot', 'scot.id', '=', 'lc.status_cotacao_id');
        $query->leftJoin('modalidades as m', 'm.id', '=', 'lc.modalidade_id');

        $query->whereNull('lc.deleted_at');
        $query->orderBy('lc.data_limite_proposta',  'desc');
        $query->orderBy('lc.exercicio',);
        $query->orderBy('lc.num_compra');




        if (isset($atributes->licitacao_id) && !empty($atributes->licitacao_id)) {
            $query->where('lc.id', '=', $atributes->licitacao_id);
        }

        if (isset($atributes->cliente_id) && !empty($atributes->cliente_id)) {
            $query->where('lc.cliente_id', '=', $atributes->cliente_id);
        }

        if (isset($atributes->modalidade_id) && !empty($atributes->modalidade_id)) {
            $query->where('lc.modalidade_id', '=', $atributes->modalidade_id);
        }

        if (isset($atributes->status_licitacoes_id) && !empty($atributes->status_licitacoes_id)) {
            $query->where('lc.status_licitacoes_id', '=', $atributes->status_licitacoes_id);
        }

        if (isset($atributes->status_compra_id) && !empty($atributes->status_compra_id)) {
            $query->where('scomp.id', '=', $atributes->status_compra_id);
        }


        if (isset($atributes->data_limite_proposta_inicio) && !empty($atributes->data_limite_proposta_inicio)) {
            $data_limite_proposta_inicio = Carbon::parse($atributes->data_limite_proposta_inicio)->startOfMonth()->toDateTimeString();
            $query->where('lc.data_limite_proposta', '>=', $data_limite_proposta_inicio);
        }

        if (isset($atributes->data_limite_proposta_final) && !empty($atributes->data_limite_proposta_final)) {
            $data_limite_proposta_final = Carbon::parse($atributes->data_limite_proposta_final)->endOfMonth()->toDateTimeString();
            $query->where('lc.data_limite_proposta', '<=', $data_limite_proposta_final);
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('lc.num_compra', 'like', '%' . $chave . '%')
                    ->orWhere('lc.exercicio', 'like', '%' . $chave . '%')
                    ->orWhere('cl.nome', 'like', '%' . $chave . '%')
                    ->orWhere('lc.exercicio', 'like', '%' . $chave . '%')
                    ->orWhere('cl.nome', 'like', '%' . $chave . '%')
                    ->orWhere('sl.nome', 'like', '%' . $chave . '%')
                    ->orWhere('scomp.nome', 'like', '%' . $chave . '%')
                    ->orWhere('scot.nome', 'like', '%' . $chave . '%')
                    ->orWhere('m.nome', 'like', '%' . $chave . '%')
                ;
            });
        }

        $paginate = new PaginateService;
        $Licitacoes = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $Licitacoes->appends((array) $atributes);
        $Licitacoes = collect($Licitacoes)->toArray();
        return $Licitacoes;
    }
    public function getLicitacoessAsync($params)
    {

        $query = DB::table('licitacoes as lc')
            ->whereNull('lc.deleted_at')
            ->select('lc.id', 'lc.num_compra', 'lc.exercicio');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('lc.num_compra', 'like', '%' . $chave . '%');
                $query->OrWhere('lc.exercicio', 'like', '%' . $chave . '%');
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getLicitacoesId(int $id_ContasBancaria)
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
            $query->from('contas_bancarias as cb');
            $query->leftJoin('despesas as dp', 'dp.id', '=', 'lanc.despesa_id');
            $query->leftJoin('status_jobs as s_jobs', 's_jobs.id', '=', 'lanc.status_job_id');
            $query->whereNull('lanc.deleted_at');
            $query->where('lanc.id', $id_ContasBancaria);

            $Licitacoes = collect($query->first())->toArray();

            return $Licitacoes;
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function handleLookupsLicitacoesItens()
    {
        $data = [];
        $data['statusClassificacoes'] = StatusClassificacao::all();
        return $data;
    }

    public function handleAddLicitacoesItens(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d\TH:i', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');
            $result->licitacoes = $this->createLicitacoes($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditLicitacoesItens(object $atributes)
    {

        try {

            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d\TH:i', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');
            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d H:i:s', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');


            DB::beginTransaction();
            $result = (object)[];
            $result->licitacoess = $this->updateLicitacoes($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteLicitacoesItens(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->licitacoess = $this->deleteLicitacoesItens($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateLicitacoesItens(object $atributes)
    {
        try {
            $queryUpdate = Licitacoes::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Licitacoes');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Licitacoes alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteLicitacoesItens(int  $id)
    {
        try {
            $queryDelete = Licitacoes::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Licitacoes');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Licitacoes deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createLicitacoesItens(object $atributes)
    {
        try {
            $atributes->status_job_id = 1;
            $newData = new Licitacoes((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a Licitacoes');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'Licitacoes cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getLicitacoesItensPaginate($atributes)
    {

        $query = DB::query();

        $query->select(
            'li.id',
            'li.id AS licitacao_itens_id',
            'lc.id AS licitacao_id',


        );


        $query->from('licitacao_itens as li');
        $query->join('licitacoes as lc', 'lc.id', '=', 'li.licitacao_id');
        $query->leftJoin('status_itens as si', 'si.id', '=', 'li.status_itens_id');
        $query->leftJoin('status_classificacoes as sclas', 'sclas.id', '=', 'li.status_classificacoes_id');



        $query->where('lc.id', $atributes->licitacao_id);
        $query->whereNull('li.deleted_at');
        $query->whereNull('lc.deleted_at');
        $query->orderBy('li.num_item',  'asc');

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('li.descricao', 'like', '%' . $chave . '%');
            });
        }
        $paginate = new PaginateService;
        $Licitacoes = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $Licitacoes->appends((array) $atributes);
        $Licitacoes = collect($Licitacoes)->toArray();
        return $Licitacoes;
    }
    public function getLicitacoesItensAsync($params)
    {

        $query = DB::table('licitacoes as lc')
            ->whereNull('lc.deleted_at')
            ->select('lc.id', 'lc.num_compra', 'lc.exercicio');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('lc.num_compra', 'like', '%' . $chave . '%');
                $query->OrWhere('lc.exercicio', 'like', '%' . $chave . '%');
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getLicitacoesItensId(int $id_ContasBancaria)
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
            $query->from('contas_bancarias as cb');
            $query->leftJoin('despesas as dp', 'dp.id', '=', 'lanc.despesa_id');
            $query->leftJoin('status_jobs as s_jobs', 's_jobs.id', '=', 'lanc.status_job_id');
            $query->whereNull('lanc.deleted_at');
            $query->where('lanc.id', $id_ContasBancaria);

            $Licitacoes = collect($query->first())->toArray();

            return $Licitacoes;
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getLicitacaoData(object $atributes)
    {
        try {

            $modalidade = $atributes->modalidade == '8'
                ? 1
                : ($atributes->modalidade == '6'
                    ? 2
                    : null);



            $query = DB::query();
            $query->select(
                'lct.id',
                'lct.link_pcnp',
                'uc.id as unidade_compradoras_id',
                'oc.id as orgaos_id',
                'scomp.id AS status_compra_id',
                'scomp.nome AS status_compra',
                'sl.id AS status_licitacoes_id',
                'sl.nome AS status_licitacao',
            );


            $query->from('licitacoes as lct');
            $query->join('unidade_compradoras as uc', 'uc.id', '=', 'lct.unidade_compradoras_id');
            $query->join('orgaos as oc', 'oc.id', '=', 'uc.orgaos_id');
            $query->join('modalidades as mdld', 'mdld.id', '=', 'lct.modalidade_id');
            $query->leftJoin('status_compras as scomp', 'scomp.id', '=', 'lct.status_compra_id');
            $query->leftJoin('status_licitacoes as sl', 'sl.id', '=', 'lct.status_licitacoes_id');
            $query->where('lct.num_compra', $atributes->num_compra);
            $query->where('lct.exercicio', $atributes->exercicio);
            $query->where('uc.codigo', $atributes->codigo);
            $query->where('mdld.id', $modalidade);
            $query->whereNull('lct.deleted_at');

            $licitacoes = collect($query->first())->toArray();


            // return  $modalidade;


            return $licitacoes;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
