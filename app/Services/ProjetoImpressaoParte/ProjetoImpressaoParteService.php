<?php

namespace App\Services\ProjetoImpressaoParte;

use App\Models\ProjetoImpressao;
use App\Models\ProjetoImpressaoParte;
use App\Repositories\ProjetoImpressao\ProjetoImpressaoRepository;
use App\Repositories\ProjetoImpressaoParte\ProjetoImpressaoParteRepository;
use App\Services\PaginateService;
use App\Services\ProjetoImpressao\ProjetoImpressaoCustoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjetoImpressaoParteService
{
    private ProjetoImpressaoParteRepository $_repository;

    private ProjetoImpressaoRepository $_projetoRepository;

    private ProjetoImpressaoParteItemService $_itemService;

    private ProjetoImpressaoCustoService $_custoService;

    public function __construct()
    {
        $this->_repository        = new ProjetoImpressaoParteRepository();
        $this->_projetoRepository = new ProjetoImpressaoRepository();
        $this->_itemService       = new ProjetoImpressaoParteItemService();
        $this->_custoService      = new ProjetoImpressaoCustoService();
    }

    public function handleLookupsProjetoImpressaoParte(): array
    {
        return [
            'projetosImpressao' => ProjetoImpressao::whereNull('deleted_at')
                ->orderBy('nome_original_projeto')
                ->get(['id', 'nome_original_projeto', 'codigo_projeto']),
        ];
    }

    public function handleAddProjetoImpressaoParte(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                     = (object) [];
            $result->projetoImpressaoParte = $this->createProjetoImpressaoParte($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditProjetoImpressaoParte(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                     = (object) [];
            $result->projetoImpressaoParte = $this->updateProjetoImpressaoParte($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteProjetoImpressaoParte(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                     = (object) [];
            $result->projetoImpressaoParte = $this->deleteProjetoImpressaoParte($id);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createProjetoImpressaoParte(object $atributes): object
    {
        $this->validarProjetoExiste($atributes);

        $newData = $this->_repository->create([
            'id_projeto_impressao' => (int) $atributes->id_projeto_impressao,
            'nome_parte'           => $atributes->nome_parte,
        ]);

        return (object) [
            'data'    => $this->getProjetoImpressaoParteId($newData->id),
            'status'  => true,
            'message' => 'Parte do projeto cadastrada com sucesso!',
        ];
    }

    public function updateProjetoImpressaoParte(object $atributes): object
    {
        $record = $this->_repository->findById($atributes->id);

        if (!$record) {
            throw new Exception('Parte do projeto não encontrada', 404);
        }

        $this->validarProjetoExiste($atributes);

        $saved = $this->_repository->update($record, [
            'id_projeto_impressao' => (int) $atributes->id_projeto_impressao,
            'nome_parte'           => $atributes->nome_parte,
        ]);

        if (!$saved) {
            throw new Exception('Não foi possível editar a parte do projeto', 500);
        }

        return (object) [
            'data'    => $this->getProjetoImpressaoParteId($atributes->id),
            'status'  => true,
            'message' => 'Parte do projeto alterada com sucesso!',
        ];
    }

    public function deleteProjetoImpressaoParte(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Parte do projeto não encontrada', 404);
        }

        $idProjeto = (int) $record->id_projeto_impressao;

        $this->_itemService->deleteItensByParte((int) $record->id);

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir a parte do projeto', 500);
        }

        $this->_custoService->recalcularCustosProjeto($idProjeto);

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Parte do projeto excluída com sucesso!',
        ];
    }

    public function deletePartesByProjeto(int $idProjeto): void
    {
        ProjetoImpressaoParte::where('id_projeto_impressao', $idProjeto)
            ->whereNull('deleted_at')
            ->get()
            ->each(function (ProjetoImpressaoParte $parte) {
                $this->_itemService->deleteItensByParte((int) $parte->id);
                $parte->delete();
            });
    }

    public function getProjetoImpressaoPartePaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.id_projeto_impressao',
            'proj.nome_original_projeto',
            'proj.codigo_projeto',
            'ent.nome_parte',
            'ent.custo_filamento',
            'ent.custo_energia',
            'ent.custo_desgaste',
            'ent.custo_total',
            'ent.created_at',
        );

        $query->selectRaw('(SELECT COUNT(*) FROM projetos_impressao_parte_itens i WHERE i.id_projeto_impressao_parte = ent.id AND i.deleted_at IS NULL) as total_itens');

        $query->from('projetos_impressao_partes as ent');
        $query->join('projetos_impressao as proj', 'proj.id', '=', 'ent.id_projeto_impressao');
        $query->whereNull('ent.deleted_at');
        $query->whereNull('proj.deleted_at');
        $query->orderBy('proj.nome_original_projeto');
        $query->orderBy('ent.nome_parte');

        if (!empty($atributes->id_projeto_impressao)) {
            $query->where('ent.id_projeto_impressao', $atributes->id_projeto_impressao);
        }

        if (!empty($atributes->nome_parte)) {
            $chave = $atributes->nome_parte;
            $query->where('ent.nome_parte', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.nome_parte', 'like', '%' . $chave . '%')
                    ->orWhere('proj.nome_original_projeto', 'like', '%' . $chave . '%')
                    ->orWhere('proj.codigo_projeto', 'like', '%' . $chave . '%');
            });
        }

        $paginate  = new PaginateService();
        $resultado = $paginate->_paginate(
            $query,
            $atributes->page,
            $atributes->perPage,
            ['path' => $atributes->url, 'query' => $atributes->query]
        );

        $idsPartes = $resultado->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $custosPorParte = $this->_custoService->calcularCustosExibicaoPorPartes($idsPartes);

        $resultado->getCollection()->transform(function ($row) use ($custosPorParte) {
            $data = (array) $row;
            $idParte = (int) ($data['id'] ?? 0);
            $custos = $custosPorParte[$idParte] ?? $this->_custoService->formatarCustosResposta(null);

            return (object) array_merge($data, $custos);
        });

        $resultado->appends((array) $atributes);

        return collect($resultado)->toArray();
    }

    public function getProjetoImpressaoParteId(int|string $id): array
    {
        $record = DB::table('projetos_impressao_partes as ent')
            ->join('projetos_impressao as proj', 'proj.id', '=', 'ent.id_projeto_impressao')
            ->select(
                'ent.id',
                'ent.id_projeto_impressao',
                'ent.nome_parte',
                'ent.custo_filamento',
                'ent.custo_energia',
                'ent.custo_desgaste',
                'ent.custo_total',
                'ent.created_at',
                'ent.updated_at',
                'proj.nome_original_projeto',
                'proj.codigo_projeto',
            )
            ->whereNull('ent.deleted_at')
            ->whereNull('proj.deleted_at')
            ->where('ent.id', $id)
            ->first();

        if (!$record) {
            throw new Exception('Parte do projeto não encontrada', 404);
        }

        $data = collect($record)->toArray();
        $data = $this->_custoService->appendCustosExibicaoRegistro($data, (int) $id);
        $data['itens'] = $this->_itemService->getItensByParte((int) $id);

        return $data;
    }

    public function getProjetoImpressaoParteAsync(object $params): array
    {
        $query = DB::table('projetos_impressao_partes as ent')
            ->join('projetos_impressao as proj', 'proj.id', '=', 'ent.id_projeto_impressao')
            ->whereNull('ent.deleted_at')
            ->whereNull('proj.deleted_at')
            ->select(
                'ent.id',
                'ent.nome_parte',
                'proj.nome_original_projeto',
            );

        if (!empty($params->id_projeto_impressao)) {
            $query->where('ent.id_projeto_impressao', $params->id_projeto_impressao);
        }

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.nome_parte', 'like', '%' . $chave . '%')
                    ->orWhere('proj.nome_original_projeto', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->orderBy('ent.nome_parte')->get()->toArray();
    }

    public function getPartesByProjeto(int $idProjeto): array
    {
        return DB::table('projetos_impressao_partes as ent')
            ->whereNull('ent.deleted_at')
            ->where('ent.id_projeto_impressao', $idProjeto)
            ->orderBy('ent.nome_parte')
            ->get()
            ->map(function ($parte) {
                $data = (array) $parte;
                $data = $this->_custoService->appendCustosExibicaoRegistro($data, (int) $parte->id);
                $data['itens'] = $this->_itemService->getItensByParte((int) $parte->id);

                return $data;
            })
            ->toArray();
    }

    private function validarProjetoExiste(object $atributes): void
    {
        $projeto = $this->_projetoRepository->findById((int) $atributes->id_projeto_impressao);

        if (!$projeto) {
            throw new Exception('O projeto de impressão informado não existe.', 422);
        }
    }
}
