<?php

namespace App\Services\ProjetoImpressaoParte;

use App\Models\ProjetoImpressao;
use App\Models\ProjetoImpressaoParte;
use App\Repositories\ProjetoImpressao\ProjetoImpressaoRepository;
use App\Repositories\ProjetoImpressaoParte\ProjetoImpressaoParteRepository;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjetoImpressaoParteService
{
    /**
     * @var ProjetoImpressaoParteRepository $_repository
     */
    private ProjetoImpressaoParteRepository $_repository;

    /**
     * @var ProjetoImpressaoParteTempoService $_tempoService
     */
    private ProjetoImpressaoParteTempoService $_tempoService;

    /**
     * @var ProjetoImpressaoParteCalculoService $_calculoService
     */
    private ProjetoImpressaoParteCalculoService $_calculoService;

    /**
     * @var ProjetoImpressaoRepository $_projetoRepository
     */
    private ProjetoImpressaoRepository $_projetoRepository;

    public function __construct()
    {
        $this->_repository        = new ProjetoImpressaoParteRepository();
        $this->_tempoService      = new ProjetoImpressaoParteTempoService();
        $this->_calculoService    = new ProjetoImpressaoParteCalculoService();
        $this->_projetoRepository = new ProjetoImpressaoRepository();
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsProjetoImpressaoParte(): array
    {
        return [
            'projetosImpressao' => ProjetoImpressao::whereNull('deleted_at')
                ->orderBy('nome_original_projeto')
                ->get(['id', 'nome_original_projeto', 'codigo_projeto', 'bico_padrao']),
            'tiposSuporte'       => ProjetoImpressaoParteConfig::TIPOS_SUPORTE,
            'alturasPorBico'     => ProjetoImpressaoParteConfig::ALTURAS_POR_BICO,
            'temperaturaBicoPadrao' => ProjetoImpressaoParteConfig::TEMPERATURA_BICO_PADRAO,
            'temperaturaMesaPadrao' => ProjetoImpressaoParteConfig::TEMPERATURA_MESA_PADRAO,
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

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

    // =========================================================
    // CRUD FUNCTIONS
    // =========================================================

    public function createProjetoImpressaoParte(object $atributes): object
    {
        $this->validarConfiguracaoParte($atributes);

        $newData = $this->_repository->create($this->buildParteData($atributes));

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

        $this->validarConfiguracaoParte($atributes);

        $saved = $this->_repository->update($record, $this->buildParteData($atributes));

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

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir a parte do projeto', 500);
        }

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
            ->each(fn (ProjetoImpressaoParte $parte) => $parte->delete());
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getProjetoImpressaoPartePaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.id_projeto_impressao',
            'proj.nome_original_projeto',
            'proj.codigo_projeto',
            'ent.nome_parte',
            'ent.altura_camada',
            'ent.temperatura_bico',
            'ent.temperatura_mesa',
            'ent.tempo_impressao',
            'ent.peso_parte',
            'ent.peso_suporte',
            'ent.peso_corado',
            'ent.peso_torre',
            'ent.usa_suporte',
            'ent.usa_brim',
            'ent.usa_engomagem',
            'ent.loops_parede',
            'ent.created_at',
        );

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

        $resultado->getCollection()->transform(function ($item) {
            return (object) $this->_calculoService->appendCamposVirtuais($item);
        });

        $resultado->appends((array) $atributes);

        return collect($resultado)->toArray();
    }

    public function getProjetoImpressaoParteId(int|string $id): array
    {
        $record = DB::table('projetos_impressao_partes as ent')
            ->join('projetos_impressao as proj', 'proj.id', '=', 'ent.id_projeto_impressao')
            ->select(
                'ent.*',
                'proj.nome_original_projeto',
                'proj.codigo_projeto',
                'proj.bico_padrao',
            )
            ->whereNull('ent.deleted_at')
            ->whereNull('proj.deleted_at')
            ->where('ent.id', $id)
            ->first();

        if (!$record) {
            throw new Exception('Parte do projeto não encontrada', 404);
        }

        return $this->_calculoService->appendCamposVirtuais(collect($record)->toArray());
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
            ->map(fn ($parte) => $this->_calculoService->appendCamposVirtuais((array) $parte))
            ->toArray();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function buildParteData(object $atributes): array
    {
        $usaSuporte   = (bool) $atributes->usa_suporte;
        $usaEngomagem = (bool) $atributes->usa_engomagem;

        $pesoParte    = $this->_calculoService->normalizarPeso($atributes->peso_parte, 'O peso da parte', true);
        $pesoSuporte  = $this->_calculoService->normalizarPeso($atributes->peso_suporte ?? 0, 'O peso de suporte');
        $pesoCorado   = $this->_calculoService->normalizarPeso($atributes->peso_corado ?? 0, 'O peso corado');
        $pesoTorre    = $this->_calculoService->normalizarPeso($atributes->peso_torre ?? 0, 'O peso da torre');

        return [
            'id_projeto_impressao'      => (int) $atributes->id_projeto_impressao,
            'nome_parte'                => $atributes->nome_parte,
            'altura_camada'             => $atributes->altura_camada ?? 0.20,
            'temperatura_bico'          => (int) ($atributes->temperatura_bico ?? ProjetoImpressaoParteConfig::TEMPERATURA_BICO_PADRAO),
            'temperatura_mesa'          => (int) ($atributes->temperatura_mesa ?? ProjetoImpressaoParteConfig::TEMPERATURA_MESA_PADRAO),
            'tempo_impressao'           => (string) $atributes->tempo_impressao,
            'peso_parte'                => $pesoParte,
            'peso_suporte'              => $pesoSuporte,
            'peso_corado'               => $pesoCorado,
            'peso_torre'                => $pesoTorre,
            'usa_suporte'               => $usaSuporte,
            'angulo_suporte'            => $usaSuporte ? ($atributes->angulo_suporte ?? null) : null,
            'tipo_suporte'              => $usaSuporte ? ($atributes->tipo_suporte ?? null) : null,
            'distancia_z_inferior'      => $usaSuporte ? ($atributes->distancia_z_inferior ?? null) : null,
            'quantidade_voltas_suporte' => $usaSuporte ? ($atributes->quantidade_voltas_suporte ?? null) : null,
            'usa_brim'                  => (bool) $atributes->usa_brim,
            'usa_engomagem'             => $usaEngomagem,
            'velocidade_engomagem'      => $usaEngomagem ? ($atributes->velocidade_engomagem ?? null) : null,
            'fluxo_engomagem'           => $usaEngomagem ? ($atributes->fluxo_engomagem ?? null) : null,
            'loops_parede'              => (int) ($atributes->loops_parede ?? 2),
        ];
    }

    private function validarConfiguracaoParte(object $atributes): void
    {
        $this->_tempoService->validarFormato((string) $atributes->tempo_impressao);

        $projeto    = $this->_projetoRepository->findById((int) $atributes->id_projeto_impressao);
        $bicoPadrao = $projeto?->bico_padrao;

        if (!$bicoPadrao) {
            throw new Exception('O projeto de impressão informado não existe.', 422);
        }

        $alturaCamada      = number_format((float) $atributes->altura_camada, 2, '.', '');
        $alturasPermitidas = ProjetoImpressaoParteConfig::ALTURAS_POR_BICO[(string) $bicoPadrao] ?? [];

        if (!in_array($alturaCamada, $alturasPermitidas, true)) {
            throw new Exception(
                'A altura de camada informada não é compatível com o bico padrão do projeto.',
                422
            );
        }
    }
}
