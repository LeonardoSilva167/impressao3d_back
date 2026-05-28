<?php

namespace App\Services\ProjetoImpressao;

use App\Models\Cor;
use App\Models\ProjetoImpressao;
use App\Repositories\ProjetoImpressao\ProjetoImpressaoRepository;
use App\Services\PaginateService;
use App\Services\ProjetoImpressaoCor\ProjetoImpressaoCorService;
use App\Services\ProjetoImpressaoParte\ProjetoImpressaoParteConfig;
use App\Services\ProjetoImpressaoParte\ProjetoImpressaoParteService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjetoImpressaoService
{
    /**
     * @var ProjetoImpressaoRepository $_repository
     */
    private ProjetoImpressaoRepository $_repository;

    /**
     * @var ProjetoImpressaoCorService $_corService
     */
    private ProjetoImpressaoCorService $_corService;

    /**
     * @var ProjetoImpressaoParteService $_parteService
     */
    private ProjetoImpressaoParteService $_parteService;

    /**
     * @var ProjetoImpressaoTempoService $_tempoService
     */
    private ProjetoImpressaoTempoService $_tempoService;

    public function __construct()
    {
        $this->_repository   = new ProjetoImpressaoRepository();
        $this->_corService   = new ProjetoImpressaoCorService();
        $this->_parteService = new ProjetoImpressaoParteService();
        $this->_tempoService = new ProjetoImpressaoTempoService();
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsProjetoImpressao(): array
    {
        return [
            'cores'       => Cor::whereNull('deleted_at')->orderBy('descricao')->get(['id', 'descricao', 'codigo', 'hexadecimal']),
            'bicosPadrao' => ProjetoImpressaoParteConfig::BICOS_PADRAO,
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

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

    // =========================================================
    // CRUD FUNCTIONS
    // =========================================================

    public function createProjetoImpressao(object $atributes): object
    {
        $this->validateCodigoProjetoUnico($atributes->codigo_projeto);

        $tempoTotalHoras = $this->_tempoService->converterParaHorasMinutos($atributes->tempo_total_horas);
        $pesoTotalGramas = $this->_tempoService->normalizarValorNumerico($atributes->peso_total_gramas, 'O peso total do projeto');
        $cores            = $atributes->cores ?? [];

        $this->_corService->validarSomaPesos($cores, $pesoTotalGramas);

        $newData = $this->_repository->create([
            'url_projeto'           => $atributes->url_projeto,
            'nome_original_projeto' => $atributes->nome_original_projeto,
            'codigo_projeto'        => $atributes->codigo_projeto,
            'descricao_projeto'     => $atributes->descricao_projeto,
            'bico_padrao'           => (string) ($atributes->bico_padrao ?? '0.4'),
            'tempo_total_horas'     => $tempoTotalHoras,
            'peso_total_gramas'     => $pesoTotalGramas,
        ]);

        $this->_corService->persistCores((int) $newData->id, $cores);

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

        $tempoTotalHoras = $this->_tempoService->converterParaHorasMinutos($atributes->tempo_total_horas);
        $pesoTotalGramas = $this->_tempoService->normalizarValorNumerico($atributes->peso_total_gramas, 'O peso total do projeto');
        $cores           = $atributes->cores ?? [];

        $this->_corService->validarSomaPesos($cores, $pesoTotalGramas);

        $saved = $this->_repository->update($record, [
            'url_projeto'           => $atributes->url_projeto,
            'nome_original_projeto' => $atributes->nome_original_projeto,
            'codigo_projeto'        => $atributes->codigo_projeto,
            'descricao_projeto'     => $atributes->descricao_projeto,
            'bico_padrao'           => (string) ($atributes->bico_padrao ?? '0.4'),
            'tempo_total_horas'     => $tempoTotalHoras,
            'peso_total_gramas'     => $pesoTotalGramas,
        ]);

        if (!$saved) {
            throw new Exception('Não foi possível editar o projeto de impressão', 500);
        }

        $this->_corService->syncCores((int) $record->id, $cores);

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
        $this->_corService->deleteCoresByProjeto((int) $record->id);

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

    // =========================================================
    // QUERIES
    // =========================================================

    public function getProjetoImpressaoPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.url_projeto',
            'ent.nome_original_projeto',
            'ent.codigo_projeto',
            'ent.descricao_projeto',
            'ent.bico_padrao',
            'ent.tempo_total_horas',
            'ent.peso_total_gramas',
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
                'ent.bico_padrao',
                'ent.tempo_total_horas',
                'ent.peso_total_gramas',
                'ent.created_at',
            )
            ->whereNull('ent.deleted_at')
            ->where('ent.id', $id)
            ->first();

        if (!$record) {
            throw new Exception('Projeto de impressão não encontrado', 404);
        }

        $data           = collect($record)->toArray();
        $data['cores']  = $this->_corService->getCoresByProjeto((int) $id);
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

    // =========================================================
    // HELPERS
    // =========================================================

    private function validateCodigoProjetoUnico(string $codigo, ?int $ignoreId = null): void
    {
        $existente = $this->_repository->findByCodigoProjeto($codigo, $ignoreId);

        if ($existente) {
            throw new Exception('Já existe um projeto com este código.', 422);
        }
    }
}
