<?php

namespace App\Services\ProjetoImpressaoParteItem;

use App\Models\Cor;
use App\Models\ProjetoImpressaoParte;
use App\Models\ProjetoImpressaoParteItem;
use App\Repositories\ProjetoImpressaoParte\ProjetoImpressaoParteRepository;
use App\Repositories\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemRepository;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProjetoImpressaoParteItemService
{
    private ProjetoImpressaoParteItemRepository $_repository;

    private ProjetoImpressaoParteItemTempoService $_tempoService;

    private ProjetoImpressaoParteItemCalculoService $_calculoService;

    private ProjetoImpressaoParteRepository $_parteRepository;

    public function __construct()
    {
        $this->_repository       = new ProjetoImpressaoParteItemRepository();
        $this->_tempoService     = new ProjetoImpressaoParteItemTempoService();
        $this->_calculoService   = new ProjetoImpressaoParteItemCalculoService();
        $this->_parteRepository  = new ProjetoImpressaoParteRepository();
    }

    public function handleLookupsProjetoImpressaoParteItem(): array
    {
        return [
            'partes' => ProjetoImpressaoParte::with('projeto:id,nome_original_projeto,codigo_projeto')
                ->whereNull('deleted_at')
                ->orderBy('nome_parte')
                ->get(['id', 'id_projeto_impressao', 'nome_parte']),
            'cores' => Cor::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'codigo', 'hexadecimal']),
            'tiposSuporte'          => ProjetoImpressaoParteItemConfig::TIPOS_SUPORTE,
            'temperaturaBicoPadrao' => ProjetoImpressaoParteItemConfig::TEMPERATURA_BICO_PADRAO,
            'temperaturaMesaPadrao' => ProjetoImpressaoParteItemConfig::TEMPERATURA_MESA_PADRAO,
        ];
    }

    public function handleAddProjetoImpressaoParteItem(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result = (object) [];
            $result->projetoImpressaoParteItem = $this->createProjetoImpressaoParteItem($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditProjetoImpressaoParteItem(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result = (object) [];
            $result->projetoImpressaoParteItem = $this->updateProjetoImpressaoParteItem($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteProjetoImpressaoParteItem(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result = (object) [];
            $result->projetoImpressaoParteItem = $this->deleteProjetoImpressaoParteItem($id);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createProjetoImpressaoParteItem(object $atributes): object
    {
        $this->validarParteExiste($atributes);
        $this->_tempoService->validarFormato((string) $atributes->tempo_impressao);

        $newData = $this->_repository->create($this->buildItemData($atributes));

        return (object) [
            'data'    => $this->getProjetoImpressaoParteItemId($newData->id),
            'status'  => true,
            'message' => 'Item da parte cadastrado com sucesso!',
        ];
    }

    public function updateProjetoImpressaoParteItem(object $atributes): object
    {
        $record = $this->_repository->findById($atributes->id);

        if (!$record) {
            throw new Exception('Item da parte não encontrado', 404);
        }

        $this->validarParteExiste($atributes);
        $this->_tempoService->validarFormato((string) $atributes->tempo_impressao);

        $saved = $this->_repository->update($record, $this->buildItemData($atributes));

        if (!$saved) {
            throw new Exception('Não foi possível editar o item da parte', 500);
        }

        return (object) [
            'data'    => $this->getProjetoImpressaoParteItemId($atributes->id),
            'status'  => true,
            'message' => 'Item da parte alterado com sucesso!',
        ];
    }

    public function deleteProjetoImpressaoParteItem(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Item da parte não encontrado', 404);
        }

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir o item da parte', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Item da parte excluído com sucesso!',
        ];
    }

    public function deleteItensByParte(int $idParte): void
    {
        ProjetoImpressaoParteItem::where('id_projeto_impressao_parte', $idParte)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn (ProjetoImpressaoParteItem $item) => $item->delete());
    }

    public function getProjetoImpressaoParteItemPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.id_projeto_impressao_parte',
            'parte.nome_parte',
            'parte.id_projeto_impressao',
            'proj.nome_original_projeto',
            'proj.codigo_projeto',
            'ent.nome_item',
            'ent.id_cor',
            'cor.descricao as cor_descricao',
            'cor.hexadecimal as cor_hexadecimal',
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

        $query->from('projetos_impressao_parte_itens as ent');
        $query->join('projetos_impressao_partes as parte', 'parte.id', '=', 'ent.id_projeto_impressao_parte');
        $query->join('projetos_impressao as proj', 'proj.id', '=', 'parte.id_projeto_impressao');
        $query->leftJoin('cores as cor', 'cor.id', '=', 'ent.id_cor');
        $query->whereNull('ent.deleted_at');
        $query->whereNull('parte.deleted_at');
        $query->whereNull('proj.deleted_at');
        $query->orderBy('proj.nome_original_projeto');
        $query->orderBy('parte.nome_parte');
        $query->orderBy('ent.nome_item');

        if (!empty($atributes->id_projeto_impressao_parte)) {
            $query->where('ent.id_projeto_impressao_parte', $atributes->id_projeto_impressao_parte);
        }

        if (!empty($atributes->id_projeto_impressao)) {
            $query->where('parte.id_projeto_impressao', $atributes->id_projeto_impressao);
        }

        if (!empty($atributes->nome_item)) {
            $chave = $atributes->nome_item;
            $query->where('ent.nome_item', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.nome_item', 'like', '%' . $chave . '%')
                    ->orWhere('parte.nome_parte', 'like', '%' . $chave . '%')
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

    public function getProjetoImpressaoParteItemId(int|string $id): array
    {
        $record = DB::table('projetos_impressao_parte_itens as ent')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'ent.id_projeto_impressao_parte')
            ->join('projetos_impressao as proj', 'proj.id', '=', 'parte.id_projeto_impressao')
            ->leftJoin('cores as cor', 'cor.id', '=', 'ent.id_cor')
            ->select(
                'ent.*',
                'parte.nome_parte',
                'parte.id_projeto_impressao',
                'proj.nome_original_projeto',
                'proj.codigo_projeto',
                'cor.descricao as cor_descricao',
                'cor.codigo as cor_codigo',
                'cor.hexadecimal as cor_hexadecimal',
            )
            ->whereNull('ent.deleted_at')
            ->whereNull('parte.deleted_at')
            ->whereNull('proj.deleted_at')
            ->where('ent.id', $id)
            ->first();

        if (!$record) {
            throw new Exception('Item da parte não encontrado', 404);
        }

        return $this->_calculoService->appendCamposVirtuais(collect($record)->toArray());
    }

    public function getProjetoImpressaoParteItemAsync(object $params): array
    {
        $query = DB::table('projetos_impressao_parte_itens as ent')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'ent.id_projeto_impressao_parte')
            ->whereNull('ent.deleted_at')
            ->whereNull('parte.deleted_at')
            ->select('ent.id', 'ent.nome_item', 'parte.nome_parte');

        if (!empty($params->id_projeto_impressao_parte)) {
            $query->where('ent.id_projeto_impressao_parte', $params->id_projeto_impressao_parte);
        }

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('ent.nome_item', 'like', '%' . $chave . '%')
                    ->orWhere('parte.nome_parte', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->orderBy('ent.nome_item')->get()->toArray();
    }

    public function getItensByParte(int $idParte): array
    {
        return DB::table('projetos_impressao_parte_itens as ent')
            ->leftJoin('cores as cor', 'cor.id', '=', 'ent.id_cor')
            ->select(
                'ent.*',
                'cor.descricao as cor_descricao',
                'cor.hexadecimal as cor_hexadecimal',
            )
            ->whereNull('ent.deleted_at')
            ->where('ent.id_projeto_impressao_parte', $idParte)
            ->orderBy('ent.nome_item')
            ->get()
            ->map(fn ($item) => $this->_calculoService->appendCamposVirtuais((array) $item))
            ->toArray();
    }

    private function buildItemData(object $atributes): array
    {
        $usaSuporte   = (bool) $atributes->usa_suporte;
        $usaEngomagem = (bool) $atributes->usa_engomagem;

        $pesoParte   = $this->_calculoService->normalizarPeso($atributes->peso_parte, 'O peso da parte', true);
        $pesoSuporte = $this->_calculoService->normalizarPeso($atributes->peso_suporte ?? 0, 'O peso de suporte');
        $pesoCorado  = $this->_calculoService->normalizarPeso($atributes->peso_corado ?? 0, 'O peso corado');
        $pesoTorre   = $this->_calculoService->normalizarPeso($atributes->peso_torre ?? 0, 'O peso da torre');

        return [
            'id_projeto_impressao_parte' => (int) $atributes->id_projeto_impressao_parte,
            'nome_item'                  => $atributes->nome_item,
            'id_cor'                     => (int) $atributes->id_cor,
            'altura_camada'              => $atributes->altura_camada ?? 0.20,
            'temperatura_bico'           => (int) ($atributes->temperatura_bico ?? ProjetoImpressaoParteItemConfig::TEMPERATURA_BICO_PADRAO),
            'temperatura_mesa'           => (int) ($atributes->temperatura_mesa ?? ProjetoImpressaoParteItemConfig::TEMPERATURA_MESA_PADRAO),
            'loops_parede'               => (int) ($atributes->loops_parede ?? 2),
            'tempo_impressao'            => $this->_tempoService->normalizar((string) $atributes->tempo_impressao)
                ?? (string) $atributes->tempo_impressao,
            'peso_parte'                 => $pesoParte,
            'peso_suporte'               => $pesoSuporte,
            'peso_corado'                => $pesoCorado,
            'peso_torre'                 => $pesoTorre,
            'usa_suporte'                => $usaSuporte,
            'angulo_suporte'             => $usaSuporte ? ($atributes->angulo_suporte ?? null) : null,
            'tipo_suporte'               => $usaSuporte ? ($atributes->tipo_suporte ?? null) : null,
            'distancia_z_inferior'       => $usaSuporte ? ($atributes->distancia_z_inferior ?? null) : null,
            'quantidade_voltas_suporte'  => $usaSuporte ? ($atributes->quantidade_voltas_suporte ?? null) : null,
            'usa_brim'                   => (bool) $atributes->usa_brim,
            'usa_engomagem'              => $usaEngomagem,
            'velocidade_engomagem'       => $usaEngomagem ? ($atributes->velocidade_engomagem ?? null) : null,
            'fluxo_engomagem'            => $usaEngomagem ? ($atributes->fluxo_engomagem ?? null) : null,
        ];
    }

    private function validarParteExiste(object $atributes): void
    {
        $parte = $this->_parteRepository->findById((int) $atributes->id_projeto_impressao_parte);

        if (!$parte) {
            throw new Exception('A parte do projeto informada não existe.', 422);
        }
    }
}
