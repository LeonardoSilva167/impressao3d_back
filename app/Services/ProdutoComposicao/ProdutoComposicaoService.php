<?php

namespace App\Services\ProdutoComposicao;

use App\Models\Cor;
use App\Models\ProdutoVariacao;
use App\Models\ProjetoImpressao;
use App\Repositories\Filamento\FilamentoRepository;
use App\Repositories\ProdutoBase\ProdutoBaseRepository;
use App\Repositories\ProdutoComposicao\ProdutoComposicaoRepository;
use App\Repositories\ProdutoComposicaoCor\ProdutoComposicaoCorRepository;
use App\Repositories\ProdutoVariacao\ProdutoVariacaoRepository;
use App\Repositories\ProdutoVariacaoFilamento\ProdutoVariacaoFilamentoRepository;
use App\Repositories\ProjetoImpressao\ProjetoImpressaoRepository;
use App\Services\Filamento\FilamentoService;
use App\Services\PaginateService;
use App\Services\ProjetoImpressao\ProjetoImpressaoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProdutoComposicaoService
{
    private ProdutoComposicaoRepository $_repository;

    private ProdutoComposicaoCorRepository $_corRepository;

    private ProdutoVariacaoRepository $_variacaoRepository;

    private ProdutoVariacaoFilamentoRepository $_filamentoRepository;

    private ProdutoBaseRepository $_produtoRepository;

    private ProjetoImpressaoRepository $_projetoRepository;

    private FilamentoRepository $_filamentoRepo;

    private ProdutoComposicaoVariacaoService $_variacaoService;

    private ProjetoImpressaoService $_projetoService;

    private ProjetoImpressaoParteItemCalculoService $_itemCalculoService;

    public function __construct()
    {
        $this->_repository          = new ProdutoComposicaoRepository();
        $this->_corRepository       = new ProdutoComposicaoCorRepository();
        $this->_variacaoRepository  = new ProdutoVariacaoRepository();
        $this->_filamentoRepository = new ProdutoVariacaoFilamentoRepository();
        $this->_produtoRepository   = new ProdutoBaseRepository();
        $this->_projetoRepository   = new ProjetoImpressaoRepository();
        $this->_filamentoRepo       = new FilamentoRepository();
        $this->_variacaoService     = new ProdutoComposicaoVariacaoService();
        $this->_projetoService      = new ProjetoImpressaoService();
        $this->_itemCalculoService  = new ProjetoImpressaoParteItemCalculoService();
    }

    public function handleLookupsProdutoComposicao(): array
    {
        return [
            'produtos' => DB::table('produtos_base')
                ->whereNull('deleted_at')
                ->orderBy('descricao_produto')
                ->get(['id', 'descricao_produto', 'sku_base']),
            'projetosImpressao' => ProjetoImpressao::whereNull('deleted_at')
                ->orderBy('nome_original_projeto')
                ->get(['id', 'nome_original_projeto', 'codigo_projeto']),
        ];
    }

    public function handleAddProdutoComposicao(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->produtoComposicao = $this->createProdutoComposicao($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditProdutoComposicao(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->produtoComposicao = $this->updateProdutoComposicao($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteProdutoComposicao(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result                    = (object) [];
            $result->produtoComposicao = $this->deleteProdutoComposicao($id);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleSalvarCoresParte(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $idComposicao = (int) $atributes->id_composicao;
            $idParte      = (int) $atributes->id_parte;

            $this->validateComposicaoExiste($idComposicao);
            $this->validatePartePertenceComposicao($idComposicao, $idParte);

            $itensProcessados = $this->processarCoresPartePayload(
                $idComposicao,
                $idParte,
                $atributes->itens ?? []
            );

            $idsItensParte = array_column($itensProcessados, 'id_item_projeto');

            $this->_filamentoRepository->deleteByParteId($idComposicao, $idParte);
            $this->_variacaoRepository->deleteByParteId($idComposicao, $idParte);
            $this->_corRepository->deleteByParteId($idComposicao, $idParte);

            foreach ($itensProcessados as $item) {
                foreach ($item['cores'] as $cor) {
                    $this->_corRepository->create([
                        'id_composicao'   => $idComposicao,
                        'id_parte'        => $idParte,
                        'id_item_projeto' => $item['id_item_projeto'],
                        'tipo_cor'        => $cor['tipo_cor'],
                        'id_cor'          => $cor['id_cor'],
                    ]);
                }
            }

            DB::commit();

            return (object) [
                'data'    => $this->carregarConfiguracaoParte($idComposicao, $idParte),
                'status'  => true,
                'message' => 'Cores da parte configuradas com sucesso!',
            ];
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleGerarVariacoes(int $idComposicao, ?int $idParte = null, ?int $idItemProjeto = null): object
    {
        $this->validateComposicaoExiste($idComposicao);

        if ($idParte !== null) {
            $this->validatePartePertenceComposicao($idComposicao, $idParte);
        }

        return (object) [
            'data'    => $this->_variacaoService->gerarVariacoesPreview($idComposicao, $idParte, $idItemProjeto),
            'status'  => true,
            'message' => 'Preview das variações individuais dos itens gerado com sucesso!',
        ];
    }

    public function handleConfirmarVariacoes(int $idComposicao, ?int $idParte = null, ?int $idItemProjeto = null): object
    {
        try {
            DB::beginTransaction();

            $this->validateComposicaoExiste($idComposicao);

            if ($idParte !== null) {
                $this->validatePartePertenceComposicao($idComposicao, $idParte);
            }

            $result = $this->_variacaoService->confirmarVariacoes($idComposicao, $idParte, $idItemProjeto);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleSalvarFilamentos(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $idComposicao = (int) $atributes->id_composicao;
            $idParte      = isset($atributes->id_parte) && $atributes->id_parte !== null && $atributes->id_parte !== ''
                ? (int) $atributes->id_parte
                : null;
            $idItemProjeto = isset($atributes->id_item_projeto) && $atributes->id_item_projeto !== null && $atributes->id_item_projeto !== ''
                ? (int) $atributes->id_item_projeto
                : null;

            $this->validateComposicaoExiste($idComposicao);

            if ($idParte !== null) {
                $this->validatePartePertenceComposicao($idComposicao, $idParte);
            }

            if ($idItemProjeto !== null) {
                $this->_filamentoRepository->deleteByVariacaoIds(
                    $this->_variacaoRepository->getByComposicaoId($idComposicao, $idParte)
                        ->where('id_item_projeto', $idItemProjeto)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->toArray()
                );
            } elseif ($idParte !== null) {
                $this->_filamentoRepository->deleteByParteId($idComposicao, $idParte);
            } else {
                $this->_filamentoRepository->deleteByComposicaoId($idComposicao);
            }

            $result = $this->_variacaoService->salvarFilamentos(
                $idComposicao,
                $atributes->filamentos ?? [],
                $idParte,
                $idItemProjeto
            );

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createProdutoComposicao(object $atributes): object
    {
        $this->validateComposicaoUnicaPorProduto((int) $atributes->id_produto);
        $this->validateProdutoExiste((int) $atributes->id_produto);
        $this->validateProjetoExiste((int) $atributes->id_projeto_impressao);

        $composicao = $this->_repository->create([
            'id_produto'           => (int) $atributes->id_produto,
            'id_projeto_impressao' => (int) $atributes->id_projeto_impressao,
        ]);

        return (object) [
            'data'    => $this->getProdutoComposicaoId($composicao->id),
            'status'  => true,
            'message' => 'Composição do produto cadastrada com sucesso!',
        ];
    }

    public function updateProdutoComposicao(object $atributes): object
    {
        $record = $this->_repository->findById($atributes->id);

        if (!$record) {
            throw new Exception('Composição do produto não encontrada', 404);
        }

        $projetoAlterado = (int) $record->id_projeto_impressao !== (int) $atributes->id_projeto_impressao;
        $idProduto       = isset($atributes->id_produto)
            ? (int) $atributes->id_produto
            : (int) $record->id_produto;

        $this->validateComposicaoUnicaPorProduto($idProduto, (int) $atributes->id);
        $this->validateProdutoExiste($idProduto);
        $this->validateProjetoExiste((int) $atributes->id_projeto_impressao);

        $saved = $this->_repository->update($record, [
            'id_produto'           => $idProduto,
            'id_projeto_impressao' => (int) $atributes->id_projeto_impressao,
        ]);

        if (!$saved) {
            throw new Exception('Não foi possível editar a composição do produto', 500);
        }

        if ($projetoAlterado) {
            $this->removerDependenciasComposicao((int) $record->id);
        }

        return (object) [
            'data'    => $this->getProdutoComposicaoId($atributes->id),
            'status'  => true,
            'message' => 'Composição do produto alterada com sucesso!',
        ];
    }

    public function deleteProdutoComposicao(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Composição do produto não encontrada', 404);
        }

        $this->removerDependenciasComposicao((int) $record->id);

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir a composição do produto', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Composição do produto excluída com sucesso!',
        ];
    }

    public function getProdutoComposicaoPaginate(object $atributes): array
    {
        $query = $this->_repository->getPaginateQuery();
        $this->applyFiltros($query, $atributes);

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

    public function getProdutoComposicaoId(int|string $id): array
    {
        $record = $this->_repository->findByIdWithRelations($id);

        if (!$record) {
            throw new Exception('Composição do produto não encontrada', 404);
        }

        $partes = $this->getPartesResumoComposicao((int) $record->id, (int) $record->id_projeto_impressao);

        return [
            'id'                   => (int) $record->id,
            'id_produto'           => (int) $record->id_produto,
            'id_projeto_impressao' => (int) $record->id_projeto_impressao,
            'created_at'           => $record->created_at,
            'updated_at'           => $record->updated_at,
            'produto'              => [
                'descricao_produto' => $record->descricao_produto,
                'sku_base'          => $record->sku_base,
                'codigo_base'       => $record->codigo_base,
            ],
            'projeto'              => [
                'nome_original_projeto' => $record->nome_original_projeto,
                'codigo_projeto'        => $record->codigo_projeto,
                'descricao_projeto'     => $record->descricao_projeto,
                'partes'                => $partes,
            ],
            'quantidade_variacoes' => $this->_variacaoRepository->countByComposicaoId((int) $record->id),
        ];
    }

    public function carregarConfiguracaoParte(int $idComposicao, int $idParte): array
    {
        $record = $this->_repository->findByIdWithRelations($idComposicao);

        if (!$record) {
            throw new Exception('Composição do produto não encontrada', 404);
        }

        $this->validatePartePertenceComposicao($idComposicao, $idParte);

        $parte = DB::table('projetos_impressao_partes as parte')
            ->where('parte.id', $idParte)
            ->where('parte.id_projeto_impressao', $record->id_projeto_impressao)
            ->whereNull('parte.deleted_at')
            ->first();

        if (!$parte) {
            throw new Exception('Parte do projeto não encontrada.', 404);
        }

        $coresPorItem = $this->_corRepository->getByParteId($idComposicao, $idParte)
            ->groupBy('id_item_projeto');

        $variacoesPorItem = collect($this->_variacaoService->mapVariacoesComFilamentos($idComposicao, $idParte))
            ->groupBy('id_item_projeto');

        $itens = DB::table('projetos_impressao_parte_itens as item')
            ->select(
                'item.id',
                'item.nome_item',
                'item.peso_parte',
                'item.peso_suporte',
                'item.peso_corado',
                'item.peso_torre',
                'item.tempo_impressao',
            )
            ->where('item.id_projeto_impressao_parte', $idParte)
            ->whereNull('item.deleted_at')
            ->orderBy('item.nome_item')
            ->get()
            ->map(function ($item) use ($coresPorItem, $variacoesPorItem) {
                $coresItem = collect($coresPorItem->get((int) $item->id, collect()));

                return [
                    'id'              => (int) $item->id,
                    'nome_item'       => $item->nome_item,
                    'peso_total'      => $this->_itemCalculoService->calcularPesoTotal(
                        (float) ($item->peso_parte ?? 0),
                        (float) ($item->peso_suporte ?? 0),
                        (float) ($item->peso_corado ?? 0),
                        (float) ($item->peso_torre ?? 0),
                    ),
                    'tempo_impressao' => $item->tempo_impressao,
                    'cores'           => ProdutoComposicaoCorMapper::mapCoresPorTipo($coresItem),
                    'variacoes'       => $variacoesPorItem
                        ->get((int) $item->id, collect())
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return [
            'parte'             => [
                'id'         => $idParte,
                'nome_parte' => $parte->nome_parte,
            ],
            'itens'             => $itens,
            'cores_disponiveis' => Cor::whereNull('deleted_at')
                ->orderBy('descricao')
                ->get(['id', 'descricao', 'codigo', 'hexadecimal']),
            'tipos_cor'         => ProdutoVariacao::TIPOS_COR,
            'filamentos'        => $this->getFilamentosLookup(),
        ];
    }

    public function getProdutoComposicaoAsync(object $params): array
    {
        $query = $this->_repository->getAsyncQuery();

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('pb.descricao_produto', 'like', '%' . $chave . '%')
                    ->orWhere('pb.sku_base', 'like', '%' . $chave . '%')
                    ->orWhere('pi.nome_original_projeto', 'like', '%' . $chave . '%')
                    ->orWhere('pi.codigo_projeto', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
    }

    public function carregarDadosComposicao(int $idProduto, int $idProjetoImpressao): array
    {
        $this->validateProdutoExiste($idProduto);
        $this->validateProjetoExiste($idProjetoImpressao);

        $produto = $this->_produtoRepository->findByIdWithRelations($idProduto);

        if (!$produto) {
            throw new Exception('Produto base não encontrado', 404);
        }

        $partes = DB::table('projetos_impressao_partes as parte')
            ->select(
                'parte.id',
                'parte.nome_parte',
                DB::raw('(SELECT COUNT(*) FROM projetos_impressao_parte_itens item WHERE item.id_projeto_impressao_parte = parte.id AND item.deleted_at IS NULL) as quantidade_itens'),
            )
            ->where('parte.id_projeto_impressao', $idProjetoImpressao)
            ->whereNull('parte.deleted_at')
            ->orderBy('parte.nome_parte')
            ->get()
            ->map(fn ($parte) => [
                'id'               => (int) $parte->id,
                'nome_parte'       => $parte->nome_parte,
                'quantidade_itens' => (int) $parte->quantidade_itens,
            ])
            ->values()
            ->toArray();

        return [
            'produto' => [
                'id'                => (int) $produto->id,
                'descricao_produto' => $produto->descricao_produto,
                'sku_base'          => $produto->sku_base,
                'codigo_base'       => $produto->codigo_base,
            ],
            'projeto' => [
                'id'                    => $idProjetoImpressao,
                'partes'                => $partes,
            ],
        ];
    }

    private function getPartesResumoComposicao(int $idComposicao, int $idProjeto): array
    {
        return DB::table('projetos_impressao_partes as parte')
            ->select(
                'parte.id',
                'parte.nome_parte',
                DB::raw('(SELECT COUNT(*) FROM projetos_impressao_parte_itens item WHERE item.id_projeto_impressao_parte = parte.id AND item.deleted_at IS NULL) as quantidade_itens'),
            )
            ->where('parte.id_projeto_impressao', $idProjeto)
            ->whereNull('parte.deleted_at')
            ->orderBy('parte.nome_parte')
            ->get()
            ->map(function ($parte) use ($idComposicao) {
                $idParte = (int) $parte->id;

                return [
                    'id'                  => $idParte,
                    'nome_parte'          => $parte->nome_parte,
                    'quantidade_itens'    => (int) $parte->quantidade_itens,
                    'cores_configuradas'  => $this->_corRepository->partePossuiCores($idComposicao, $idParte),
                    'variacoes_geradas'   => $this->_variacaoRepository->partePossuiVariacoes($idComposicao, $idParte),
                    'quantidade_variacoes' => $this->_variacaoRepository->countByComposicaoId($idComposicao, $idParte),
                ];
            })
            ->values()
            ->toArray();
    }

    private function processarCoresPartePayload(int $idComposicao, int $idParte, array $itensPayload): array
    {
        if (empty($itensPayload)) {
            throw new Exception('Informe ao menos um item da parte.', 422);
        }

        $itensParte = $this->getItensParteMap($idComposicao, $idParte);
        $processados = [];
        $idsEnviados = [];

        foreach ($itensPayload as $itemPayload) {
            $itemPayload = (object) $itemPayload;
            $idItem      = (int) $itemPayload->id_item_projeto;

            if (!isset($itensParte[$idItem])) {
                throw new Exception('Item informado não pertence à parte selecionada.', 422);
            }

            if (in_array($idItem, $idsEnviados, true)) {
                throw new Exception('Item duplicado na configuração da parte.', 422);
            }

            $idsEnviados[] = $idItem;

            $processados[] = [
                'id_item_projeto' => $idItem,
                'cores'           => $this->normalizarCoresItemPorTipo($itemPayload, $itensParte[$idItem]['nome_item']),
            ];
        }

        sort($idsEnviados);
        $idsParte = array_keys($itensParte);
        sort($idsParte);

        if ($idsEnviados !== $idsParte) {
            throw new Exception('Informe cores para todos os itens da parte.', 422);
        }

        return $processados;
    }

    private function normalizarCoresItemPorTipo(object $itemPayload, string $nomeItem): array
    {
        $mapasTipo = [
            ProdutoVariacao::TIPO_PRIMARIA   => ['cores_primarias', 'cores_primaria', 'cor_primaria', 'cor_primarias'],
            ProdutoVariacao::TIPO_SECUNDARIA => ['cores_secundarias', 'cores_secundaria', 'cor_secundaria', 'cor_secundarias'],
            ProdutoVariacao::TIPO_TERCIARIA  => ['cores_terciarias', 'cores_terciaria', 'cor_terciaria', 'cor_terciarias'],
        ];

        $processadas = [];
        $idsUsados   = [];

        foreach ($mapasTipo as $tipoCor => $campos) {
            $lista = [];

            foreach ($campos as $campo) {
                if (!empty($itemPayload->{$campo}) && is_array($itemPayload->{$campo})) {
                    $lista = $itemPayload->{$campo};
                    break;
                }
            }

            foreach ($this->normalizarListaIdsCor($lista, $nomeItem, $tipoCor) as $idCor) {
                if (in_array($idCor, $idsUsados, true)) {
                    throw new Exception(
                        'Cor duplicada informada para o item "' . $nomeItem . '".',
                        422
                    );
                }

                $idsUsados[] = $idCor;
                $processadas[] = [
                    'tipo_cor' => $tipoCor,
                    'id_cor'   => $idCor,
                ];
            }
        }

        if (empty($processadas) && !empty($itemPayload->cores) && is_array($itemPayload->cores)) {
            foreach ($this->normalizarListaIdsCor($itemPayload->cores, $nomeItem, ProdutoVariacao::TIPO_PRIMARIA) as $idCor) {
                if (in_array($idCor, $idsUsados, true)) {
                    throw new Exception(
                        'Cor duplicada informada para o item "' . $nomeItem . '".',
                        422
                    );
                }

                $idsUsados[] = $idCor;
                $processadas[] = [
                    'tipo_cor' => ProdutoVariacao::TIPO_PRIMARIA,
                    'id_cor'   => $idCor,
                ];
            }
        }

        if (empty($processadas)) {
            throw new Exception(
                'O item "' . $nomeItem . '" deve possuir ao menos uma cor configurada.',
                422
            );
        }

        return $processadas;
    }

    private function normalizarListaIdsCor(array $coresPayload, string $nomeItem, string $tipoCor): array
    {
        $ids = [];

        foreach ($coresPayload as $cor) {
            $idCor = is_array($cor)
                ? (int) ($cor['id_cor'] ?? $cor['id'] ?? 0)
                : (int) $cor;

            if ($idCor <= 0) {
                throw new Exception(
                    'Cor inválida informada para o item "' . $nomeItem . '" (' . strtolower($tipoCor) . ').',
                    422
                );
            }

            if (in_array($idCor, $ids, true)) {
                throw new Exception(
                    'Cor duplicada informada para o item "' . $nomeItem . '" (' . strtolower($tipoCor) . ').',
                    422
                );
            }

            $this->validateCorExiste($idCor);
            $ids[] = $idCor;
        }

        return $ids;
    }

    private function getItensParteMap(int $idComposicao, int $idParte): array
    {
        $record = $this->_repository->findById($idComposicao);

        if (!$record) {
            throw new Exception('Composição do produto não encontrada', 404);
        }

        $itens = DB::table('projetos_impressao_parte_itens as item')
            ->select('item.id', 'item.nome_item')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'item.id_projeto_impressao_parte')
            ->where('parte.id', $idParte)
            ->where('parte.id_projeto_impressao', $record->id_projeto_impressao)
            ->whereNull('item.deleted_at')
            ->whereNull('parte.deleted_at')
            ->orderBy('item.id')
            ->get();

        $map = [];

        foreach ($itens as $item) {
            $map[(int) $item->id] = [
                'nome_item' => $item->nome_item,
            ];
        }

        return $map;
    }

    private function getFilamentosLookup(): array
    {
        return DB::table('filamentos as ent')
            ->select(
                'ent.id',
                'ent.resumo',
                'ent.preco_medio_grama',
                'item.preco_medio_atual as item_preco_medio_atual',
                'ent.id_item',
            )
            ->leftJoin('itens as item', function ($join) {
                $join->on('item.id', '=', 'ent.id_item')
                    ->whereNull('item.deleted_at');
            })
            ->whereNull('ent.deleted_at')
            ->orderBy('ent.resumo')
            ->get()
            ->map(function ($filamento) {
                $precoMedio = FilamentoService::resolverPrecoMedioPorGrama(
                    isset($filamento->preco_medio_grama) ? (float) $filamento->preco_medio_grama : null,
                    isset($filamento->item_preco_medio_atual) ? (float) $filamento->item_preco_medio_atual : null,
                    !empty($filamento->id_item) ? (int) $filamento->id_item : null,
                );

                return [
                    'id'                    => (int) $filamento->id,
                    'resumo'                => $filamento->resumo,
                    'preco_medio_por_grama' => $precoMedio,
                ];
            })
            ->toArray();
    }

    private function removerDependenciasComposicao(int $idComposicao): void
    {
        $this->_filamentoRepository->deleteByComposicaoId($idComposicao);
        $this->_variacaoRepository->deleteByComposicaoId($idComposicao);
        $this->_corRepository->deleteByComposicaoId($idComposicao);
    }

    private function validateComposicaoExiste(int $idComposicao): void
    {
        if (!$this->_repository->findById($idComposicao)) {
            throw new Exception('Composição do produto não encontrada', 404);
        }
    }

    private function validatePartePertenceComposicao(int $idComposicao, int $idParte): void
    {
        $record = $this->_repository->findById($idComposicao);

        if (!$record) {
            throw new Exception('Composição do produto não encontrada', 404);
        }

        $existe = DB::table('projetos_impressao_partes')
            ->where('id', $idParte)
            ->where('id_projeto_impressao', $record->id_projeto_impressao)
            ->whereNull('deleted_at')
            ->exists();

        if (!$existe) {
            throw new Exception('A parte informada não pertence ao projeto desta composição.', 422);
        }
    }

    private function validateComposicaoUnicaPorProduto(int $idProduto, int|string|null $excludeId = null): void
    {
        if ($this->_repository->findAtivaByProdutoId($idProduto, $excludeId)) {
            throw new Exception('Este produto base já possui uma composição ativa.', 422);
        }
    }

    private function validateProdutoExiste(int $idProduto): void
    {
        if (!$this->_produtoRepository->findById($idProduto)) {
            throw new Exception('O produto base informado não existe.', 422);
        }
    }

    private function validateProjetoExiste(int $idProjeto): void
    {
        if (!$this->_projetoRepository->findById($idProjeto)) {
            throw new Exception('O projeto de impressão informado não existe.', 422);
        }
    }

    private function validateCorExiste(int $idCor): void
    {
        if (!Cor::where('id', $idCor)->whereNull('deleted_at')->exists()) {
            throw new Exception('Cor informada não existe.', 422);
        }
    }

    private function applyFiltros($query, object $atributes): void
    {
        if (!empty($atributes->id_produto)) {
            $query->where('ent.id_produto', $atributes->id_produto);
        }

        if (!empty($atributes->id_projeto_impressao)) {
            $query->where('ent.id_projeto_impressao', $atributes->id_projeto_impressao);
        }

        if (!empty($atributes->descricao_produto)) {
            $chave = $atributes->descricao_produto;
            $query->where('pb.descricao_produto', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->sku_base)) {
            $chave = $atributes->sku_base;
            $query->where('pb.sku_base', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->codigo_projeto)) {
            $chave = $atributes->codigo_projeto;
            $query->where('pi.codigo_projeto', 'like', '%' . $chave . '%');
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('pb.descricao_produto', 'like', '%' . $chave . '%')
                    ->orWhere('pb.sku_base', 'like', '%' . $chave . '%')
                    ->orWhere('pi.nome_original_projeto', 'like', '%' . $chave . '%')
                    ->orWhere('pi.codigo_projeto', 'like', '%' . $chave . '%');
            });
        }
    }
}
