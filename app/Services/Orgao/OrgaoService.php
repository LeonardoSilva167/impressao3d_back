<?php

namespace App\Services\Orgao;

use App\Models\Cliente;
use App\Models\Orgao;
use App\Models\UnidadeCompradora;
use App\Services\PaginateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class OrgaoService
{

    // protected $_service_lancamentos;
    public function __construct()
    {
        // $this->_service_lancamentos = new LancamentosService();
    }

    public function handleLookupsOrgao()
    {
        $data = [];
        // $data['bancos'] = Banco::all();
        // $data['bancos'] = Banco::all();
        return $data;
    }

    public function handleAddOrgao(object $atributes)
    {

        try {
            DB::beginTransaction();
            // return true;
            $result = (object)[];


            $orgaoId = $this->createOrgao($atributes);
            if ($orgaoId) {

                // $unidadeCompradora = (Array)$atributes->unidadeCompradora;
                // foreach ($unidadeCompradora as $key => $value) {
                //     $value['orgaos_id'] = $orgaoId;
                //     $result = $this->createUnidadeCompradora($orgaoId, $value);
                // }
                $unidadeCompradora = $atributes->unidadeCompradora;
                // $unidadeCompradora = $atributes->unidadeCompradora;

                foreach ($unidadeCompradora as $value) {
                    $value->orgaos_id = $orgaoId;
                
                    $result = $this->createUnidadeCompradora($orgaoId, $value);
                }
            }


            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditOrgao(object $atributes)
    {

        try {
            DB::beginTransaction();
            $result = (object)[];
            $result = $this->updateOrgao($atributes);
            $save = $this->habdleUnidadeCompradora($atributes->id, $atributes);
            DB::commit();
            return $save;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteOrgao(int $atributes)
    {
        try {
            DB::beginTransaction();
            $result = (object)[];
            $result->Orgaos = $this->deleteOrgao($atributes);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateOrgao(object $atributes)
    {
        try {
            $queryUpdate = Orgao::where('id', $atributes->id)->first();
            $queryUpdate->fill(get_object_vars($atributes));
            $saved = $queryUpdate->save();

            if (!$saved) {
                throw new Exception('Não foi possível editar o Orgao');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Orgao alterada ocom sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function habdleUnidadeCompradora(?int $orgaoId, object $attributes)
    {

        // return ["teste",$attributes];
        try {

            $variacoesDB = DB::table('unidade_compradoras')
                ->where('orgaos_id', $orgaoId)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            $variacoesForm = collect($attributes->unidadeCompradora)
                ->pluck('id')
                ->filter()
                ->toArray();

            $idsRemovidos = array_diff($variacoesDB, $variacoesForm);
            foreach ($idsRemovidos as $idRemovido) {
                $this->deleteUnidadeCompradora($idRemovido, null);
            }

            foreach ($attributes->unidadeCompradora as $value) {

                if (!empty($value['id'])) {
                    $valueObj = (object) $value;
                    self::updateUnidadeCompradora($valueObj);
                } else {
                    $value['orgaos_id'] = $orgaoId; // return $value->orgao_nome;

                    // return ($value);
                    $this->createUnidadeCompradora($orgaoId, $value);
                }
            }
        } catch (Exception $e) {
            throw $e;
            // return mountBaseResult(false, $e->getMessage());
        }
    }

    public function deleteUnidadeCompradora(?int $id = null, ?int $orgaoId = null): bool
    {
        DB::beginTransaction();
        try {
            $query = DB::table('unidade_compradoras');

            if (!is_null($id)) {
                $query->where('id', $id);
            }

            if (!is_null($orgaoId)) {
                $query->where('orgaos_id', $orgaoId);
            }

            $deleted = $query->update([
                'updated_at' => now(),
                'deleted_at' => now(),
            ]);

            DB::commit();
            return $deleted > 0;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteOrgao(int  $id)
    {
        try {
            $queryDelete = Cliente::where('id', $id)->first();
            $saved = $queryDelete->delete();

            if (!$saved) {
                throw new Exception('Não foi possível excluir a Orgao');
            }

            return (object)[
                'data' => [],
                'status' => true,
                'message' => 'Orgao deletada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createOrgao(object $atributes)
    {
        try {
            $newData = new Orgao((array)($atributes));
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar o Orgão');
            }

            return $newData->id;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createUnidadeCompradora(int $orgaoId, $attributes)
    {


        try {
            // return $attributes;
            $newData = new UnidadeCompradora([
                'orgaos_id' => $attributes->orgaos_id,
                'codigo'    => $attributes->codigo,
                'nome'      => $attributes->nome,
                'cidade'    => $attributes->cidade,
                'uf'        => $attributes->uf,
            ]);
            $saved = $newData->save();
            if (!$saved) {
                throw new Exception('Não foi possível adicionar a Unidade Compradora');
            }
            // }
            return (object)[
                'data' => $newData->id,
                'status' => true,
                'message' => 'Unidade Compradora cadastrada com sucesso!',
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function updateUnidadeCompradora(object $attributes): int
    {
        $unidadeCompradora = UnidadeCompradora::find($attributes->id);
        if (!$unidadeCompradora) {
            throw new \Exception("Unidade Compradora não encontrada", 404);
        }

        $unidadeCompradora->fill([
            'codigo' => $attributes->codigo,
            'nome' => $attributes->nome,
            'uf' => $attributes->uf,
            'cidade' => $attributes->cidade,
        ]);

        $saved = $unidadeCompradora->save();
        if (!$saved) {
            throw new \Exception(" não foi possivel salvar", 404);
        }

        return $unidadeCompradora->id;
    }


    public function getOrgaoPaginate($atributes)
    {

        $query = DB::query();


        $query->select(
            'op.id',
            'op.id AS orgao_id',
            'op.orgao_nome',
            'uc.id AS unidade_compradora_id',
            'uc.codigo',
            'uc.nome',
            'uc.uf',
            'uc.cidade',

        );

        $query->from('orgaos as op');
        $query->leftJoin('unidade_compradoras as uc', 'uc.orgaos_id', '=', 'op.id');
        $query->whereNull('op.deleted_at');
        $query->whereNull('uc.deleted_at');

        $query->orderBy('op.orgao_nome');
        $query->orderBy('uc.nome');
        $query->orderBy('uc.codigo');
        $query->orderBy('uc.uf');
        $query->orderBy('uc.cidade');

        if (isset($atributes->codigo)) {
            $query->where('uc.codigo', $atributes->codigo);
        }
        if (isset($atributes->uf)) {
            $query->where('uc.uf', $atributes->uf);
        }
        if (isset($atributes->cidade)) {
            $query->where('uc.cidade', $atributes->cidade);
        }

        if (isset($atributes->unidadeCompradora) && isset($atributes->unidadeCompradora)) {
            $chave = $atributes->unidadeCompradora;
            $query->where(function ($query) use ($chave) {
                $query->Where('uc.codigo', 'like', '%' . $chave . '%');
                $query->OrWhere('uc.nome', 'like', '%' . $chave . '%');
            });
        }
        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('op.orgao_nome', 'like', '%' . $chave . '%');
                $query->OrWhere('uc.codigo', 'like', '%' . $chave . '%');
                $query->OrWhere('uc.nome', 'like', '%' . $chave . '%');;
            });
        }


        $paginate = new PaginateService;
        $Orgao = $paginate->_paginate($query, $atributes->page, $atributes->perPage, ['path' => $atributes->url, 'query' => $atributes->query]);
        $Orgao->appends((array) $atributes);
        $Orgao = collect($Orgao)->toArray();
        return $Orgao;
    }
    public function getOrgaosAsync($params)
    {

        $query = DB::table('Orgao as cl')
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
    public function getUnidadeCompradoraAsync($params)
    {

        $query = DB::table('unidade_compradoras as uc')
            ->whereNull('uc.deleted_at')
            ->select('uc.id', 'uc.codigo', 'uc.nome');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($query) use ($chave) {
                $query->Where('uc.nome', 'like', '%' . $chave . '%');
                $query->OrWhere('uc.codigo', 'like', '%' . $chave . '%');
            });
            $query->limit(5);
        }

        return $query->get()->toArray();
    }

    public function getOrgaoId(int $id_orgao)
    {
        try {
            $query = DB::query();
            $query->select(
                'op.id',
                'op.orgao_nome',
            );
            $query->from('orgaos as op');
            $query->whereNull('op.deleted_at');

            $orgao = collect($query->first())->toArray();

            $orgao['unidadeCompradora'] = self::getUnidadeCompradoraOrgaoId($id_orgao);

            return $orgao;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getUnidadeCompradoraOrgaoId(int $id_orgao)
    {
        try {
            $query = DB::query();
            $query->select(
                'uc.id',
                'uc.codigo',
                'uc.nome',
                'uc.uf',
                'uc.cidade',
            );
            $query->from('unidade_compradoras as uc');
            $query->whereNull('uc.deleted_at');
            $query->where('uc.orgaos_id', $id_orgao);

            $unidadesCompradoras = $query->get()->toArray();

            return $unidadesCompradoras;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
