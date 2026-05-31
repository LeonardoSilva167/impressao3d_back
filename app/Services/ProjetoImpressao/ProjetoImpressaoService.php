<?php

namespace App\Services\ProjetoImpressao;

use App\Repositories\ProjetoImpressao\ProjetoImpressaoRepository;
use App\Services\PaginateService;
use App\Services\ProjetoImpressao\ProjetoImpressaoCustoService;
use App\Services\ProjetoImpressaoParte\ProjetoImpressaoParteService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjetoImpressaoService
{
    private ProjetoImpressaoRepository $_repository;

    private ProjetoImpressaoParteService $_parteService;

    private ProjetoImpressaoCustoService $_custoService;

    public function __construct()
    {
        $this->_repository    = new ProjetoImpressaoRepository();
        $this->_parteService  = new ProjetoImpressaoParteService();
        $this->_custoService  = new ProjetoImpressaoCustoService();
    }

    public function handleLookupsProjetoImpressao(): array
    {
        return [];
    }

    public function handleAddProjetoImpressao(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->projetoImpressao = $this->createProjetoImpressao($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditProjetoImpressao(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->projetoImpressao = $this->updateProjetoImpressao($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteProjetoImpressao(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                  = (object) [];
            $result->projetoImpressao = $this->deleteProjetoImpressao($id);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createProjetoImpressao(object $atributes): object
    {
        $this->validateCodigoProjetoUnico($atributes->codigo_projeto);

        $newData = $this->_repository->create([
            'url_projeto'           => $atributes->url_projeto,
            'nome_original_projeto' => $atributes->nome_original_projeto,
            'codigo_projeto'        => $atributes->codigo_projeto,
            'descricao_projeto'     => $atributes->descricao_projeto,
        ]);

        return (object) [
            'data'    => $this->getProjetoImpressaoId($newData->id),
            'status'  => true,
            'message' => 'Projeto de impressão cadastrado com sucesso!',
        ];
    }

    public function updateProjetoImpressao(object $atributes): object
    {
        $record = $this->_repository->findById($atributes->id);

        if (!$record) {
            throw new Exception('Projeto de impressão não encontrado', 404);
        }

        $this->validateCodigoProjetoUnico($atributes->codigo_projeto, (int) $atributes->id);

        $saved = $this->_repository->update($record, [
            'url_projeto'           => $atributes->url_projeto,
            'nome_original_projeto' => $atributes->nome_original_projeto,
            'codigo_projeto'        => $atributes->codigo_projeto,
            'descricao_projeto'     => $atributes->descricao_projeto,
        ]);

        if (!$saved) {
            throw new Exception('Não foi possível editar o projeto de impressão', 500);
        }

        return (object) [
            'data'    => $this->getProjetoImpressaoId($atributes->id),
            'status'  => true,
            'message' => 'Projeto de impressão alterado com sucesso!',
        ];
    }

    public function deleteProjetoImpressao(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Projeto de impressão não encontrado', 404);
        }

        $this->_parteService->deletePartesByProjeto((int) $record->id);

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir o projeto de impressão', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Projeto de impressão excluído com sucesso!',
        ];
    }

    public function getProjetoImpressaoPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.url_projeto',
            'ent.nome_original_projeto',
            'ent.codigo_projeto',
            'ent.descricao_projeto',
            'ent.custo_filamento',
            'ent.custo_energia',
            'ent.custo_desgaste',
            'ent.custo_total',
            'ent.created_at',
        );

        $query->selectRaw('(SELECT COUNT(*) FROM projetos_impressao_partes p WHERE p.id_projeto_impressao = ent.id AND p.deleted_at IS NULL) as total_partes');

        $query->from('projetos_impressao as ent');
        $query->whereNull('ent.deleted_at');
        $query->orderBy('ent.nome_original_projeto');

        if (!empty($atributes->nome_original_projeto)) {
            $chave = $atributes->nome_original_projeto;
            $query->where('ent.nome_original_projeto', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->codigo_projeto)) {
            $chave = $atributes->codigo_projeto;
            $query->where('ent.codigo_projeto', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.nome_original_projeto', 'like', '%' . $chave . '%')
                    ->orWhere('ent.codigo_projeto', 'like', '%' . $chave . '%')
                    ->orWhere('ent.descricao_projeto', 'like', '%' . $chave . '%');
            });
        }

        $paginate  = new PaginateService();
        $resultado = $paginate->_paginate(
            $query,
            $atributes->page,
            $atributes->perPage,
            ['path' => $atributes->url, 'query' => $atributes->query]
        );

        $idsProjetos = $resultado->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $custosPorProjeto = $this->_custoService->calcularCustosExibicaoPorProjetos($idsProjetos);

        $resultado->getCollection()->transform(function ($row) use ($custosPorProjeto) {
            $data = (array) $row;
            $idProjeto = (int) ($data['id'] ?? 0);
            $custos = $custosPorProjeto[$idProjeto] ?? $this->_custoService->formatarCustosResposta(null);

            return (object) array_merge($data, $custos);
        });

        $resultado->appends((array) $atributes);

        return collect($resultado)->toArray();
    }

    public function getProjetoImpressaoId(int|string $id): array
    {
        $record = DB::table('projetos_impressao as ent')
            ->select(
                'ent.id',
                'ent.url_projeto',
                'ent.nome_original_projeto',
                'ent.codigo_projeto',
                'ent.descricao_projeto',
                'ent.custo_filamento',
                'ent.custo_energia',
                'ent.custo_desgaste',
                'ent.custo_total',
                'ent.created_at',
            )
            ->whereNull('ent.deleted_at')
            ->where('ent.id', $id)
            ->first();

        if (!$record) {
            throw new Exception('Projeto de impressão não encontrado', 404);
        }

        $data = collect($record)->toArray();
        $data = $this->_custoService->appendCustosExibicaoProjeto($data, (int) $id);
        $data['partes'] = $this->_parteService->getPartesByProjeto((int) $id);

        return $data;
    }

    public function getProjetoImpressaoAsync(object $params): array
    {
        $query = DB::table('projetos_impressao as ent')
            ->whereNull('ent.deleted_at')
            ->select('ent.id', 'ent.nome_original_projeto', 'ent.codigo_projeto');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.nome_original_projeto', 'like', '%' . $chave . '%')
                    ->orWhere('ent.codigo_projeto', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->orderBy('ent.nome_original_projeto')->get()->toArray();
    }

    private function validateCodigoProjetoUnico(string $codigo, ?int $ignoreId = null): void
    {
        $existente = $this->_repository->findByCodigoProjeto($codigo, $ignoreId);

        if ($existente) {
            throw new Exception('Já existe um projeto com este código.', 422);
        }
    }
}
