<?php

namespace App\Services\AnaliseEdital;

use App\Models\AnaliseEdital;
use App\Models\Modalidade;
use App\Models\StatusClassificacao;
use App\Models\StatusCompra;
use App\Models\StatusLicitacao;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class AnaliseEditalService
{

    // protected $_service_lancamentos;
    public function __construct()
    {
        // $this->_service_lancamentos = new LancamentosService();
    }

    public function handleLookupsAnaliseEdital()
    {
        $data = [];
        $data['modalidades'] = Modalidade::all();
        $data['statusAnaliseEdital'] = StatusLicitacao::all();
        $data['statusCompras'] = StatusCompra::all();
        return $data;
    }

    public function handleAddAnaliseEdital(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->tags = $this->gerarTags($atributes->descricao); 

            // dd($result->tags);
            return $result->tags;


 
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    static public function gerarTags($texto)
    {
        $stopwords = [
            'a','o','e','de','da','do','das','dos','para','por',
            'em','no','na','nos','nas','um','uma','que','com',
            'as','os','se','ao','à','às','até','entre'
        ];
    
        // limpar texto
        $clean = strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $texto));
        $words = preg_split('/\s+/', $clean);
    
        // remover stopwords
        $words = array_diff($words, $stopwords);
    
        // remover duplicadas
        return array_values(array_unique($words));
    }
















    public function handleEditAnaliseEdital(object $atributes)
    {

        try {

            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d\TH:i', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');
            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d H:i:s', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');


            DB::beginTransaction();
            $result = (object)[];
            $result->analiseEditals = $this->updateAnaliseEdital($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteAnaliseEdital(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->analiseEditals = $this->deleteAnaliseEdital($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateAnaliseEdital(object $atributes)
    {
        try {
            $queryUpdate = AnaliseEdital::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o AnaliseEdital');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'AnaliseEdital alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteAnaliseEdital(int  $id)
    {
        try {
            $queryDelete = AnaliseEdital::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a AnaliseEdital');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'AnaliseEdital deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createAnaliseEdital(object $atributes)
    {
        try {
            $newData = new AnaliseEdital((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a AnaliseEdital');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'AnaliseEdital cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getAnaliseEditalPaginate($atributes)
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
            'cl.id AS cliente_id',
            'cl.codigo AS cliente_codigo',
            'cl.nome AS cliente_nome',
            'cl.uf AS cliente_uf',
            'cl.cidade AS cliente_cidade',
            'sl.id AS status_AnaliseEdital_id', 
            'sl.nome AS status_licitacao', 
            'scomp.id AS status_compra_id',
            'scomp.nome AS status_compra',
            'scot.id AS status_cotacao_id',
            'scot.nome AS status_cotacao', 
            'm.id AS modalidade_id',
            'm.nome AS modalidade_nome',
 
        );
        $query->from('AnaliseEdital as lc');
        $query->leftJoin('clientes as cl', 'cl.id', '=', 'lc.cliente_id');
        $query->leftJoin('status_AnaliseEdital as sl', 'sl.id', '=', 'lc.status_AnaliseEdital_id');
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

        if (isset($atributes->status_AnaliseEdital_id) && !empty($atributes->status_AnaliseEdital_id)) {
            $query->where('lc.status_AnaliseEdital_id', '=', $atributes->status_AnaliseEdital_id);
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
        $AnaliseEdital = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $AnaliseEdital->appends((array) $atributes);
        $AnaliseEdital = collect($AnaliseEdital)->toArray();
        return $AnaliseEdital;
    }
    public function getAnaliseEditalsAsync($params)
    {

        $query = DB::table('AnaliseEdital as lc')
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

    public function getAnaliseEditalId(int $id_ContasBancaria) {
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

            $AnaliseEdital = collect($query->first())->toArray();

            return $AnaliseEdital;

        } catch (Exception $e) {
            throw $e;
        }
    }

    
    public function handleLookupsAnaliseEditalItens()
    {
        $data = [];
        $data['statusClassificacoes'] = StatusClassificacao::all();
        return $data;
    }

    public function handleAddAnaliseEditalItens(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d\TH:i', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');
            $result->analiseEdital = $this->createAnaliseEdital($atributes);
 
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditAnaliseEditalItens(object $atributes)
    {

        try {

            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d\TH:i', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');
            // $atributes->data_limite_proposta = Carbon::createFromFormat('Y-m-d H:i:s', $atributes->data_limite_proposta)->format('Y-m-d H:i:s');


            DB::beginTransaction();
            $result = (object)[];
            $result->analiseEditals = $this->updateAnaliseEdital($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteAnaliseEditalItens(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->analiseEditals = $this->deleteAnaliseEditalItens($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateAnaliseEditalItens(object $atributes)
    {
        try {
            $queryUpdate = AnaliseEdital::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o AnaliseEdital');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'AnaliseEdital alterada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteAnaliseEditalItens(int  $id)
    {
        try {
            $queryDelete = AnaliseEdital::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a AnaliseEdital');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'AnaliseEdital deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createAnaliseEditalItens(object $atributes)
    {
        try {
            $atributes->status_job_id = 1;
            $newData = new AnaliseEdital((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a AnaliseEdital');
            }
            return (object)[
                'data' => $newData,
                'status' => true,
                'message' => 'AnaliseEdital cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getAnaliseEditalItensPaginate($atributes)
    {

        $query = DB::query();
        
        $query->select(
            'li.id',
            'li.id AS licitacao_itens_id',
            'lc.id AS licitacao_id',

 
        );

        
        $query->from('licitacao_itens as li');
        $query->join('AnaliseEdital as lc', 'lc.id', '=', 'li.licitacao_id');
        $query->leftJoin('status_itens as si', 'si.id', '=', 'li.status_itens_id');
        $query->leftJoin('status_classificacoes as sclas', 'sclas.id', '=', 'li.status_classificacoes_id');
        
       

        $query->where('lc.id', $atributes->licitacao_id);
        $query->whereNull('li.deleted_at');
        $query->whereNull('lc.deleted_at');
        $query->orderBy('li.num_item',  'asc');

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->orWhere('li.descricao', 'like', '%' . $chave . '%')
                ;
            });
        }
        $paginate = new PaginateService;
        $AnaliseEdital = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $AnaliseEdital->appends((array) $atributes);
        $AnaliseEdital = collect($AnaliseEdital)->toArray();
        return $AnaliseEdital;
    }
    public function getAnaliseEditalItensAsync($params)
    {

        $query = DB::table('AnaliseEdital as lc')
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

    public function getAnaliseEditalItensId(int $id_ContasBancaria) {
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

            $AnaliseEdital = collect($query->first())->toArray();

            return $AnaliseEdital;

        } catch (Exception $e) {
            throw $e;
        }
    }

}
