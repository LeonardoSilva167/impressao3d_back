<?php

namespace App\Services\GradeProduto;

use App\Models\ProdutoBase;
use App\Repositories\GradeProduto\GradeProdutoRepository;
use App\Repositories\GradeProdutoCombinacao\GradeProdutoCombinacaoRepository;
use App\Repositories\GradeProdutoCombinacaoParte\GradeProdutoCombinacaoParteRepository;
use App\Repositories\GradeProdutoItem\GradeProdutoItemRepository;
use App\Repositories\GradeProdutoParte\GradeProdutoParteRepository;
use App\Repositories\ProdutoComposicao\ProdutoComposicaoRepository;
use App\Repositories\ProdutoVariacao\ProdutoVariacaoRepository;
use App\Services\PaginateService;
use App\Services\ProdutoComposicao\ProdutoComposicaoVariacaoService;
use App\Services\ProjetoImpressao\ProjetoImpressaoCustoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;
use Exception;
use Illuminate\Support\Facades\DB;

class GradeProdutoService
{
    private GradeProdutoRepository $_repository;

    private GradeProdutoCombinacaoRepository $_combinacaoRepository;

    private GradeProdutoCombinacaoParteRepository $_combinacaoParteRepository;

    private GradeProdutoItemRepository $_itemRepository;

    private GradeProdutoParteRepository $_parteRepository;

    private ProdutoComposicaoRepository $_composicaoRepository;

    private ProdutoVariacaoRepository $_variacaoRepository;

    private GradeProdutoGeracaoService $_geracaoService;

    private ProdutoComposicaoVariacaoService $_variacaoService;

    private ProjetoImpressaoParteItemCalculoService $_itemCalculoService;

    private ProjetoImpressaoCustoService $_custoService;

    public function __construct()
    {
        $this->_repository                 = new GradeProdutoRepository();
        $this->_combinacaoRepository       = new GradeProdutoCombinacaoRepository();
        $this->_combinacaoParteRepository  = new GradeProdutoCombinacaoParteRepository();
        $this->_itemRepository             = new GradeProdutoItemRepository();
        $this->_parteRepository            = new GradeProdutoParteRepository();
        $this->_composicaoRepository       = new ProdutoComposicaoRepository();
        $this->_variacaoRepository         = new ProdutoVariacaoRepository();
        $this->_geracaoService             = new GradeProdutoGeracaoService();
        $this->_variacaoService            = new ProdutoComposicaoVariacaoService();
        $this->_itemCalculoService         = new ProjetoImpressaoParteItemCalculoService();
        $this->_custoService               = new ProjetoImpressaoCustoService();
    }

    public function handleLookupsGradeProduto(): array
    {
        return [
            'produtosBase' => DB::table('produtos_base as pb')
                ->select(
                    'pb.id',
                    'pb.descricao_produto',
                    'pb.sku_base',
                    DB::raw('(SELECT COUNT(*) FROM produto_composicoes pc WHERE pc.id_produto = pb.id AND pc.deleted_at IS NULL) as possui_composicao'),
                )
                ->whereNull('pb.deleted_at')
                ->orderBy('pb.descricao_produto')
                ->get(),
        ];
    }

    public function handleAddGradeProduto(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result               = (object) [];
            $result->gradeProduto = $this->createGradeProduto($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditGradeProduto(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result               = (object) [];
            $result->gradeProduto = $this->updateGradeProduto($atributes);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteGradeProduto(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result               = (object) [];
            $result->gradeProduto = $this->deleteGradeProduto($id);

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleGerarProdutos(int $idGrade, bool $persistir = true): object
    {
        try {
            DB::beginTransaction();

            $record = $this->_repository->findById($idGrade);

            if (!$record) {
                throw new Exception('Grade de produtos não encontrada', 404);
            }

            $combinacoes = $this->montarCombinacoesPayloadFromGrade((int) $idGrade);

            if (empty($combinacoes)) {
                throw new Exception('Cadastre ao menos uma combinação na grade.', 422);
            }

            $produtos = $this->gerarProdutosParaCombinacoes(
                (int) $record->id_produto_base,
                $combinacoes,
            );

            if ($persistir) {
                $this->persistirProdutosGerados($idGrade, $produtos);
            }

            DB::commit();

            return (object) [
                'data'    => [
                    'id_grade_produto'     => $idGrade,
                    'total_combinacoes'    => count($combinacoes),
                    'total_produtos'       => count($produtos),
                    'produtos'             => $this->sanitizarProdutosPreview($produtos),
                    'produtos_persistidos' => $persistir,
                ],
                'status'  => true,
                'message' => $persistir
                    ? 'Produtos finais gerados e salvos com sucesso!'
                    : 'Preview dos produtos finais gerado com sucesso!',
            ];
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handlePreviewProdutos(object $atributes): object
    {
        $idProdutoBase = (int) ($atributes->id_produto_base ?? 0);
        $combinacoes   = $this->normalizarCombinacoesPayload(
            $atributes->combinacoes ?? [],
        );

        if ($idProdutoBase <= 0) {
            throw new Exception('O produto base é obrigatório.', 422);
        }

        if (empty($combinacoes)) {
            throw new Exception('Informe ao menos uma combinação para o preview.', 422);
        }

        $produtos = $this->gerarProdutosParaCombinacoes($idProdutoBase, $combinacoes);

        return (object) [
            'data'    => [
                'id_produto_base'   => $idProdutoBase,
                'combinacoes'       => $combinacoes,
                'total_combinacoes' => count($combinacoes),
                'total_produtos'    => count($produtos),
                'produtos'          => $this->sanitizarProdutosPreview($produtos),
            ],
            'status'  => true,
            'message' => 'Preview dos produtos finais gerado com sucesso!',
        ];
    }

    public function handleGerarGrade(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $idProdutoBase = (int) ($atributes->id_produto_base ?? 0);
            $this->validateProdutoPossuiComposicao($idProdutoBase);

            $descricao = trim((string) ($atributes->descricao ?? ''));

            if ($descricao === '') {
                $produto = DB::table('produtos_base')
                    ->where('id', $idProdutoBase)
                    ->whereNull('deleted_at')
                    ->first(['descricao_produto']);

                $descricao = 'Grade - ' . ($produto->descricao_produto ?? 'Produto');
            }

            $grade = $this->_repository->create([
                'id_produto_base' => $idProdutoBase,
                'descricao'       => $descricao,
                'status'          => $this->normalizarStatus($atributes->status ?? true),
            ]);

            $combinacoes = $this->salvarCombinacoesGrade(
                (int) $grade->id,
                $idProdutoBase,
                $atributes->combinacoes ?? [],
            );

            $produtos = $this->gerarProdutosParaCombinacoes($idProdutoBase, $combinacoes);
            $this->persistirProdutosGerados((int) $grade->id, $produtos);

            DB::commit();

            return (object) [
                'data'    => $this->getGradeProdutoGradeId($grade->id),
                'status'  => true,
                'message' => 'Grade gerada e produtos finais salvos com sucesso!',
            ];
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function createGradeProduto(object $atributes): object
    {
        $idProdutoBase = (int) ($atributes->id_produto_base ?? 0);
        $this->validateProdutoPossuiComposicao($idProdutoBase);

        $grade = $this->_repository->create([
            'id_produto_base' => $idProdutoBase,
            'descricao'       => trim((string) $atributes->descricao),
            'status'          => $this->normalizarStatus($atributes->status ?? true),
        ]);

        $combinacoes = $this->salvarCombinacoesGrade(
            (int) $grade->id,
            $idProdutoBase,
            $atributes->combinacoes ?? [],
        );

        if ($this->deveGerarProdutos($atributes)) {
            $produtos = $this->gerarProdutosParaCombinacoes($idProdutoBase, $combinacoes);
            $this->persistirProdutosGerados((int) $grade->id, $produtos);
        }

        return (object) [
            'data'    => $this->getGradeProdutoGradeId($grade->id),
            'status'  => true,
            'message' => 'Grade de produtos cadastrada com sucesso!',
        ];
    }

    public function updateGradeProduto(object $atributes): object
    {
        $record = $this->_repository->findById($atributes->id);

        if (!$record) {
            throw new Exception('Grade de produtos não encontrada', 404);
        }

        $idProdutoBase = isset($atributes->id_produto_base)
            ? (int) $atributes->id_produto_base
            : (int) $record->id_produto_base;

        $this->validateProdutoPossuiComposicao($idProdutoBase);

        $produtoAlterado = $idProdutoBase !== (int) $record->id_produto_base;

        $saved = $this->_repository->update($record, [
            'id_produto_base' => $idProdutoBase,
            'descricao'       => trim((string) ($atributes->descricao ?? $record->descricao)),
            'status'          => $this->normalizarStatus($atributes->status ?? $record->status),
        ]);

        if (!$saved) {
            throw new Exception('Não foi possível editar a grade de produtos', 500);
        }

        $combinacoesAtualizadas = null;

        if (isset($atributes->combinacoes) || $produtoAlterado) {
            $this->removerCombinacoesGrade((int) $record->id);
            $combinacoesAtualizadas = $this->salvarCombinacoesGrade(
                (int) $record->id,
                $idProdutoBase,
                $atributes->combinacoes ?? [],
            );
        }

        if ($this->deveGerarProdutos($atributes) || ($produtoAlterado && isset($atributes->combinacoes))) {
            $combinacoes = $combinacoesAtualizadas
                ?? $this->montarCombinacoesPayloadFromGrade((int) $record->id);

            $produtos = $this->gerarProdutosParaCombinacoes($idProdutoBase, $combinacoes);
            $this->persistirProdutosGerados((int) $record->id, $produtos);
        }

        return (object) [
            'data'    => $this->getGradeProdutoGradeId($record->id),
            'status'  => true,
            'message' => 'Grade de produtos alterada com sucesso!',
        ];
    }

    public function deleteGradeProduto(int|string $id): object
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Grade de produtos não encontrada', 404);
        }

        $this->_itemRepository->deleteByGradeId((int) $record->id);
        $this->removerCombinacoesGrade((int) $record->id);
        $this->_parteRepository->deleteByGradeId((int) $record->id);

        $saved = $this->_repository->delete($record);

        if (!$saved) {
            throw new Exception('Não foi possível excluir a grade de produtos', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Grade de produtos excluída com sucesso!',
        ];
    }

    public function getGradeProdutoPaginate(object $atributes): array
    {
        $query = $this->_itemRepository->getPaginateQuery();
        $this->applyFiltrosProdutos($query, $atributes);

        $paginate  = new PaginateService();
        $resultado = $paginate->_paginate(
            $query,
            $atributes->page,
            $atributes->perPage,
            ['path' => $atributes->url, 'query' => $atributes->query]
        );
        $resultado->appends((array) $atributes);

        $payload = collect($resultado)->toArray();
        $payload['data'] = array_map(
            fn ($row) => $this->formatarListagemProduto((array) $row),
            $payload['data'] ?? []
        );

        return $payload;
    }

    public function getGradeProdutoId(int|string $id): array
    {
        $record = $this->_itemRepository->findByIdWithRelations($id);

        if (!$record) {
            throw new Exception('Produto gerado não encontrado', 404);
        }

        return $this->formatarDetalheProduto((array) $record);
    }

    public function getGradeProdutoGradeId(int|string $id): array
    {
        $record = $this->_repository->findByIdWithRelations($id);

        if (!$record) {
            throw new Exception('Grade de produtos não encontrada', 404);
        }

        return $this->montarDetalheGrade($record);
    }

    private function montarDetalheGrade(object $record): array
    {
        $idGrade = (int) $record->id;
        $this->sincronizarPartesGradeSeVazio($idGrade);

        $partes      = $this->_parteRepository->getByGradeId($idGrade);
        $combinacoes = $this->montarCombinacoesResponse($idGrade);

        return [
            'id'                      => $idGrade,
            'id_produto_base'         => (int) $record->id_produto_base,
            'codigo_base'             => $record->codigo_base,
            'nome_parte'              => $this->montarNomePartesLabel($partes),
            'descricao_grade'         => $record->descricao,
            'status'                  => (bool) $record->status,
            'created_at'              => $record->created_at,
            'updated_at'              => $record->updated_at,
            'partes'                  => $partes->map(fn ($parte) => [
                'id'               => (int) $parte->id,
                'id_parte_projeto' => (int) $parte->id_parte_projeto,
                'nome_parte'       => $parte->nome_parte,
            ])->values()->toArray(),
            'combinacoes'             => $combinacoes,
            'produtos_gerados'        => $this->_itemRepository->getByGradeId($idGrade)
                ->map(fn ($row) => $this->formatarListagemProduto((array) $row))
                ->toArray(),
            'quantidade_combinacoes'  => count($combinacoes),
            'quantidade_produtos'     => $this->_itemRepository->countByGradeId($idGrade),
        ];
    }

    public function carregarComposicaoPorProdutoBase(int $idProdutoBase): array
    {
        $this->validateProdutoExiste($idProdutoBase);

        $composicao = $this->_composicaoRepository->findAtivaByProdutoId($idProdutoBase);

        if (!$composicao) {
            throw new Exception('Este produto base não possui composição cadastrada.', 422);
        }

        $produto = DB::table('produtos_base')
            ->where('id', $idProdutoBase)
            ->whereNull('deleted_at')
            ->first(['id', 'descricao_produto', 'sku_base', 'codigo_base']);

        return [
            'produto'    => [
                'id'                => (int) $produto->id,
                'descricao_produto' => $produto->descricao_produto,
                'sku_base'          => $produto->sku_base,
                'codigo_base'       => $produto->codigo_base,
            ],
            'composicao' => $this->montarComposicaoCompleta((int) $composicao->id),
        ];
    }

    public function getGradeProdutoAsync(object $params): array
    {
        $query = $this->_itemRepository->getAsyncQuery();

        if (!empty($params->palavra_chave)) {
            $this->aplicarFiltroBuscaTexto($query, (string) $params->palavra_chave, true);
            $query->limit(10);
        }

        return array_map(
            fn ($row) => $this->formatarAsyncProduto((array) $row),
            $query->get()->toArray()
        );
    }

    private function montarCombinacoesResponse(int $idGrade): array
    {
        $combinacoes = $this->_combinacaoRepository->getByGradeId($idGrade);

        return $combinacoes->map(function ($combinacao) {
            $partes = $this->_combinacaoParteRepository->getByCombinacaoId((int) $combinacao->id);

            return [
                'id'        => (int) $combinacao->id,
                'descricao' => $combinacao->descricao,
                'partes'    => $partes->map(fn ($parte) => [
                    'id'               => (int) $parte->id,
                    'id_parte_projeto' => (int) $parte->id_parte_projeto,
                    'nome_parte'       => $parte->nome_parte,
                    'quantidade'       => (int) $parte->quantidade,
                ])->values()->toArray(),
            ];
        })->values()->toArray();
    }

    private function montarCombinacoesPayloadFromGrade(int $idGrade): array
    {
        $combinacoes = [];

        foreach ($this->_combinacaoRepository->getByGradeId($idGrade) as $combinacao) {
            $partes = $this->_combinacaoParteRepository->getByCombinacaoId((int) $combinacao->id);

            $combinacoes[] = [
                'id'        => (int) $combinacao->id,
                'descricao' => $combinacao->descricao,
                'partes'    => $partes->map(fn ($parte) => [
                    'id_parte_projeto' => (int) $parte->id_parte_projeto,
                    'quantidade'       => (int) $parte->quantidade,
                ])->values()->toArray(),
            ];
        }

        return $combinacoes;
    }

    private function montarComposicaoCompleta(int $idComposicao): array
    {
        $record = $this->_composicaoRepository->findByIdWithRelations($idComposicao);

        if (!$record) {
            throw new Exception('Composição do produto não encontrada', 404);
        }

        $partes = DB::table('projetos_impressao_partes as parte')
            ->select(
                'parte.id',
                'parte.nome_parte',
                DB::raw('(SELECT COUNT(*) FROM projetos_impressao_parte_itens item WHERE item.id_projeto_impressao_parte = parte.id AND item.deleted_at IS NULL) as quantidade_itens'),
            )
            ->where('parte.id_projeto_impressao', $record->id_projeto_impressao)
            ->whereNull('parte.deleted_at')
            ->orderBy('parte.nome_parte')
            ->get()
            ->map(function ($parte) use ($idComposicao) {
                $idParte = (int) $parte->id;

                return [
                    'id'                  => $idParte,
                    'nome_parte'          => $parte->nome_parte,
                    'quantidade_itens'    => (int) $parte->quantidade_itens,
                    'itens'               => $this->montarItensParteComposicao($idComposicao, $idParte),
                    'quantidade_variacoes' => $this->_variacaoRepository->countByComposicaoId($idComposicao, $idParte),
                ];
            })
            ->values()
            ->toArray();

        return [
            'id'                   => (int) $record->id,
            'id_produto'           => (int) $record->id_produto,
            'id_projeto_impressao' => (int) $record->id_projeto_impressao,
            'projeto'              => [
                'nome_original_projeto' => $record->nome_original_projeto,
                'codigo_projeto'        => $record->codigo_projeto,
            ],
            'partes'               => $partes,
            'quantidade_variacoes' => $this->_variacaoRepository->countByComposicaoId($idComposicao),
        ];
    }

    private function montarItensParteComposicao(int $idComposicao, int $idParte): array
    {
        $variacoesPorItem = collect(
            $this->_variacaoService->mapVariacoesComFilamentos($idComposicao, $idParte)
        )->groupBy('id_item_projeto');

        return DB::table('projetos_impressao_parte_itens as item')
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
            ->map(function ($item) use ($variacoesPorItem) {
                $variacoes = $variacoesPorItem->get((int) $item->id, collect())->values()->toArray();

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
                    'variacoes'       => $variacoes,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * @param  array<int, array{descricao?: string, partes: array}>  $combinacoesPayload
     */
    private function gerarProdutosParaCombinacoes(int $idProdutoBase, array $combinacoesPayload): array
    {
        $composicao = $this->_composicaoRepository->findAtivaByProdutoId($idProdutoBase);

        if (!$composicao) {
            throw new Exception('Este produto base não possui composição cadastrada.', 422);
        }

        $produto = DB::table('produtos_base')
            ->where('id', $idProdutoBase)
            ->whereNull('deleted_at')
            ->first(['descricao_produto', 'sku_base']);

        if (!$produto) {
            throw new Exception('Produto base não encontrado', 404);
        }

        $contexto = $this->carregarContextoGeracao((int) $composicao->id, (int) $composicao->id_projeto_impressao, $combinacoesPayload);

        $todosProdutos = [];

        foreach ($combinacoesPayload as $combinacao) {
            $partes = $combinacao['partes'] ?? [];

            if (empty($partes)) {
                throw new Exception(
                    'A combinação "' . ($combinacao['descricao'] ?? '') . '" deve possuir ao menos uma parte.',
                    422
                );
            }

            $produtosCombinacao = $this->_geracaoService->gerarProdutos(
                $produto->descricao_produto,
                $produto->sku_base,
                $contexto['variacoes_por_parte'],
                $partes,
                $contexto['itens_projeto_por_id'],
            );

            $idCombinacao = isset($combinacao['id']) ? (int) $combinacao['id'] : null;
            $descricaoCombinacao = (string) ($combinacao['descricao'] ?? '');

            foreach ($produtosCombinacao as $index => $produtoGerado) {
                $produtosCombinacao[$index]['id_grade_produto_combinacao'] = $idCombinacao;
                $produtosCombinacao[$index]['descricao_combinacao']        = $descricaoCombinacao;
            }

            $todosProdutos = array_merge($todosProdutos, $produtosCombinacao);
        }

        return $todosProdutos;
    }

    /**
     * @param  array<int, array{descricao?: string, partes: array}>  $combinacoesPayload
     */
    private function carregarContextoGeracao(int $idComposicao, int $idProjeto, array $combinacoesPayload): array
    {
        $idsPartesNecessarias = [];

        foreach ($combinacoesPayload as $combinacao) {
            foreach ($combinacao['partes'] ?? [] as $parte) {
                $parte = (array) $parte;
                $idParte = (int) ($parte['id_parte_projeto'] ?? $parte['id_parte'] ?? $parte['id'] ?? 0);

                if ($idParte > 0) {
                    $idsPartesNecessarias[$idParte] = $idParte;
                }
            }
        }

        $idsPartes = array_values($idsPartesNecessarias);
        $this->validatePartesComposicao($idComposicao, $idProjeto, $idsPartes);

        $variacoesPorParte = [];
        $itensProjetoPorId = [];

        foreach ($idsPartes as $idParte) {
            $variacoes = $this->_variacaoRepository->getByComposicaoId($idComposicao, $idParte);

            if ($variacoes->isEmpty()) {
                $nomeParte = DB::table('projetos_impressao_partes')
                    ->where('id', $idParte)
                    ->value('nome_parte') ?? (string) $idParte;

                throw new Exception(
                    'A parte "' . $nomeParte . '" não possui variações confirmadas.',
                    422
                );
            }

            $variacoesComFilamento = $variacoes->filter(function ($variacao) {
                return !empty($variacao->id_filamento);
            });

            if ($variacoesComFilamento->isEmpty()) {
                $nomeParte = $variacoes->first()->nome_parte ?? (string) $idParte;
                throw new Exception(
                    'A parte "' . $nomeParte . '" possui variações sem filamentos configurados.',
                    422
                );
            }

            $itensParte = DB::table('projetos_impressao_parte_itens as item')
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
                ->get();

            foreach ($itensParte as $item) {
                $itensProjetoPorId[(int) $item->id] = $item;
            }

            $porItem = [];

            foreach ($variacoesComFilamento as $variacao) {
                $item = $itensProjetoPorId[(int) $variacao->id_item_projeto] ?? null;

                $variacao->tempo_impressao = $item->tempo_impressao ?? '00:00';
                $variacao->peso_parte      = $item->peso_parte ?? 0;
                $variacao->peso_suporte    = $item->peso_suporte ?? 0;
                $variacao->peso_corado     = $item->peso_corado ?? 0;
                $variacao->peso_torre      = $item->peso_torre ?? 0;

                $idItem = (int) $variacao->id_item_projeto;

                if (!isset($porItem[$idItem])) {
                    $porItem[$idItem] = [];
                }

                $porItem[$idItem][] = $variacao;
            }

            foreach ($itensParte as $item) {
                $idItem = (int) $item->id;

                if (empty($porItem[$idItem])) {
                    throw new Exception(
                        'O item "' . $item->nome_item . '" não possui variações com filamentos configurados.',
                        422
                    );
                }
            }

            $variacoesPorParte[$idParte] = $porItem;
        }

        return [
            'variacoes_por_parte'   => $variacoesPorParte,
            'itens_projeto_por_id'  => $itensProjetoPorId,
        ];
    }

    /**
     * @return array<int, array{descricao: string, partes: array}>
     */
    private function salvarCombinacoesGrade(int $idGrade, int $idProdutoBase, array $combinacoesPayload): array
    {
        $combinacoes = $this->normalizarCombinacoesPayload($combinacoesPayload);

        if (empty($combinacoes)) {
            throw new Exception('Cadastre ao menos uma combinação para a grade.', 422);
        }

        $composicao = $this->_composicaoRepository->findAtivaByProdutoId($idProdutoBase);

        if (!$composicao) {
            throw new Exception('Este produto base não possui composição cadastrada.', 422);
        }

        $idsPartesTodas = [];

        $salvas = [];

        foreach ($combinacoes as $combinacao) {
            foreach ($combinacao['partes'] as $parte) {
                $idsPartesTodas[] = (int) $parte['id_parte_projeto'];
            }
        }

        $this->validatePartesComposicao(
            (int) $composicao->id,
            (int) $composicao->id_projeto_impressao,
            array_values(array_unique($idsPartesTodas)),
        );

        foreach ($combinacoes as $combinacao) {
            $registro = $this->_combinacaoRepository->create([
                'id_grade_produto' => $idGrade,
                'descricao'        => $combinacao['descricao'],
            ]);

            foreach ($combinacao['partes'] as $parte) {
                $this->_combinacaoParteRepository->create([
                    'id_grade_produto_combinacao' => (int) $registro->id,
                    'id_parte_projeto'            => (int) $parte['id_parte_projeto'],
                    'quantidade'                  => (int) $parte['quantidade'],
                ]);
            }

            $salvas[] = [
                'id'        => (int) $registro->id,
                'descricao' => $combinacao['descricao'],
                'partes'    => $combinacao['partes'],
            ];
        }

        $this->salvarPartesGrade($idGrade, array_values(array_unique($idsPartesTodas)));

        return $salvas;
    }

    private function salvarPartesGrade(int $idGrade, array $idsPartes): void
    {
        $this->_parteRepository->deleteByGradeId($idGrade);

        foreach ($idsPartes as $idParte) {
            if ($idParte <= 0) {
                continue;
            }

            $this->_parteRepository->create([
                'id_grade_produto' => $idGrade,
                'id_parte_projeto' => (int) $idParte,
            ]);
        }
    }

    private function sincronizarPartesGradeSeVazio(int $idGrade): void
    {
        if (!empty($this->_parteRepository->getIdsPartesByGradeId($idGrade))) {
            return;
        }

        $idsPartes = $this->_combinacaoParteRepository->getDistinctParteIdsByGradeId($idGrade);

        if (empty($idsPartes)) {
            return;
        }

        $this->salvarPartesGrade($idGrade, $idsPartes);
    }

    private function removerCombinacoesGrade(int $idGrade): void
    {
        $combinacoes = $this->_combinacaoRepository->getByGradeId($idGrade);
        $ids = $combinacoes->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        $this->_combinacaoParteRepository->deleteByCombinacaoIds($ids);
        $this->_combinacaoRepository->deleteByGradeId($idGrade);
        $this->_parteRepository->deleteByGradeId($idGrade);
    }

    /**
     * @return array<int, array{descricao: string, partes: array<int, array{id_parte_projeto: int, quantidade: int}>}>
     */
    private function normalizarCombinacoesPayload(array $combinacoesPayload): array
    {
        $combinacoes = [];

        foreach ($combinacoesPayload as $index => $combinacao) {
            $combinacao = (array) $combinacao;
            $descricao = trim((string) ($combinacao['descricao'] ?? ''));

            if ($descricao === '') {
                $descricao = 'Combinação ' . ($index + 1);
            }

            $partesPayload = $combinacao['partes'] ?? [];
            $partes        = [];

            foreach ($partesPayload as $parte) {
                $parte = (array) $parte;
                $idParte = (int) ($parte['id_parte_projeto'] ?? $parte['id_parte'] ?? $parte['id'] ?? 0);
                $quantidade = max(1, (int) ($parte['quantidade'] ?? 1));

                if ($idParte <= 0) {
                    continue;
                }

                $partes[] = [
                    'id_parte_projeto' => $idParte,
                    'quantidade'       => $quantidade,
                ];
            }

            if (empty($partes)) {
                throw new Exception(
                    'A combinação "' . $descricao . '" deve possuir ao menos uma parte.',
                    422
                );
            }

            $combinacoes[] = [
                'descricao' => $descricao,
                'partes'    => $partes,
            ];
        }

        return $combinacoes;
    }

    private function validatePartesComposicao(int $idComposicao, int $idProjeto, array $idsPartes): void
    {
        foreach ($idsPartes as $idParte) {
            $existe = DB::table('projetos_impressao_partes')
                ->where('id', $idParte)
                ->where('id_projeto_impressao', $idProjeto)
                ->whereNull('deleted_at')
                ->exists();

            if (!$existe) {
                throw new Exception('A parte informada não pertence à composição do produto.', 422);
            }
        }
    }

    private function validateProdutoPossuiComposicao(int $idProdutoBase): void
    {
        $this->validateProdutoExiste($idProdutoBase);

        if (!$this->_composicaoRepository->findAtivaByProdutoId($idProdutoBase)) {
            throw new Exception('Este produto base não possui composição cadastrada.', 422);
        }
    }

    private function validateProdutoExiste(int $idProdutoBase): void
    {
        if (!ProdutoBase::where('id', $idProdutoBase)->whereNull('deleted_at')->exists()) {
            throw new Exception('O produto base informado não existe.', 422);
        }
    }

    private function normalizarStatus(mixed $status): bool
    {
        if (is_bool($status)) {
            return $status;
        }

        if (is_string($status)) {
            return !in_array(strtoupper($status), ['0', 'FALSE', 'INATIVA', 'INATIVO'], true);
        }

        return (bool) $status;
    }

    private function deveGerarProdutos(object $atributes): bool
    {
        if (!isset($atributes->gerar_produtos)) {
            return false;
        }

        $valor = $atributes->gerar_produtos;

        return $valor === true || $valor === 1 || $valor === '1' || $valor === 'true';
    }

    private function persistirProdutosGerados(int $idGrade, array $produtos): void
    {
        $this->_itemRepository->deleteByGradeId($idGrade);

        foreach ($produtos as $produto) {
            $this->_itemRepository->create([
                'id_grade_produto'              => $idGrade,
                'id_grade_produto_combinacao'   => $produto['id_grade_produto_combinacao'] ?? null,
                'nome_produto'                  => $produto['nome_produto'],
                'sku'                           => $produto['sku'],
                'peso_total'                    => $produto['peso_total'],
                'tempo_total'                   => $produto['tempo_total'],
                'custo_filamento'               => $produto['custo_filamento'],
                'custo_energia'                 => $produto['custo_energia'] ?? 0,
                'custo_desgaste'                => $produto['custo_desgaste'] ?? 0,
                'custo_total'                   => $produto['custo_total'] ?? $produto['custo_filamento'],
                'status'                        => true,
            ]);
        }
    }

    private function sanitizarProdutosPreview(array $produtos): array
    {
        return array_map(function ($produto) {
            unset($produto['combinacao']);

            return $produto;
        }, $produtos);
    }

    private function applyFiltrosProdutos($query, object $atributes): void
    {
        if (!empty($atributes->id_produto_base)) {
            $query->where('gp.id_produto_base', $atributes->id_produto_base);
        }

        if (!empty($atributes->id_grade_produto)) {
            $query->where('gpi.id_grade_produto', $atributes->id_grade_produto);
        }

        if (!empty($atributes->codigo_base)) {
            $query->where('pb.codigo_base', 'like', '%' . $atributes->codigo_base . '%');
        }

        if (!empty($atributes->sku)) {
            $query->where('gpi.sku', 'like', '%' . $atributes->sku . '%');
        }

        $nomeProduto = $atributes->nome_produto ?? $atributes->nome ?? null;

        if (!empty($nomeProduto)) {
            $this->aplicarFiltroBuscaTexto($query, (string) $nomeProduto);
        }

        if (!empty($atributes->parte) || !empty($atributes->partes)) {
            $this->aplicarFiltroParte($query, (string) ($atributes->parte ?? $atributes->partes));
        }

        if (isset($atributes->status) && $atributes->status !== '') {
            $query->where('gpi.status', $this->normalizarStatus($atributes->status));
        }

        $descricaoCombinacao = $atributes->descricao_combinacao ?? $atributes->descricao ?? null;

        if (!empty($descricaoCombinacao)) {
            $query->where('gpc.descricao', 'like', '%' . (string) $descricaoCombinacao . '%');
        }

        if (!empty($atributes->palavra_chave)) {
            $this->aplicarFiltroBuscaTexto($query, (string) $atributes->palavra_chave, true);
        }
    }

    private function aplicarFiltroBuscaTexto($query, string $termo, bool $incluirSkuCodigo = false): void
    {
        $like = '%' . $termo . '%';

        $query->where(function ($q) use ($like, $incluirSkuCodigo) {
            $q->where('gpi.nome_produto', 'like', $like)
                ->orWhere('gpc.descricao', 'like', $like);

            if ($incluirSkuCodigo) {
                $q->orWhere('gpi.sku', 'like', $like)
                    ->orWhere('pb.codigo_base', 'like', $like);
            }

            $q->orWhereExists(function ($sub) use ($like) {
                $sub->select(DB::raw(1))
                    ->from('grade_produto_partes as gpp_b')
                    ->join('projetos_impressao_partes as parte_b', 'parte_b.id', '=', 'gpp_b.id_parte_projeto')
                    ->whereColumn('gpp_b.id_grade_produto', 'gpi.id_grade_produto')
                    ->whereNull('gpp_b.deleted_at')
                    ->whereNull('parte_b.deleted_at')
                    ->whereNull('gpi.id_grade_produto_combinacao')
                    ->where('parte_b.nome_parte', 'like', $like);
            })->orWhereExists(function ($sub) use ($like) {
                $sub->select(DB::raw(1))
                    ->from('grade_produto_combinacao_partes as gpcp_b')
                    ->join('projetos_impressao_partes as parte_b', 'parte_b.id', '=', 'gpcp_b.id_parte_projeto')
                    ->whereColumn('gpcp_b.id_grade_produto_combinacao', 'gpi.id_grade_produto_combinacao')
                    ->whereNull('gpcp_b.deleted_at')
                    ->whereNull('parte_b.deleted_at')
                    ->where('parte_b.nome_parte', 'like', $like);
            });
        });
    }

    private function aplicarFiltroParte($query, string $parte): void
    {
        $chave = '%' . $parte . '%';

        $query->where(function ($q) use ($chave) {
            $q->whereExists(function ($sub) use ($chave) {
                $sub->select(DB::raw(1))
                    ->from('grade_produto_partes as gpp_f')
                    ->join('projetos_impressao_partes as parte_f', 'parte_f.id', '=', 'gpp_f.id_parte_projeto')
                    ->whereColumn('gpp_f.id_grade_produto', 'gpi.id_grade_produto')
                    ->whereNull('gpp_f.deleted_at')
                    ->whereNull('parte_f.deleted_at')
                    ->where('parte_f.nome_parte', 'like', $chave);
            })->orWhereExists(function ($sub) use ($chave) {
                $sub->select(DB::raw(1))
                    ->from('grade_produto_combinacao_partes as gpcp_f')
                    ->join('grade_produto_combinacoes as gpc_f', 'gpc_f.id', '=', 'gpcp_f.id_grade_produto_combinacao')
                    ->join('projetos_impressao_partes as parte_f', 'parte_f.id', '=', 'gpcp_f.id_parte_projeto')
                    ->whereColumn('gpc_f.id_grade_produto', 'gpi.id_grade_produto')
                    ->whereNull('gpcp_f.deleted_at')
                    ->whereNull('gpc_f.deleted_at')
                    ->whereNull('parte_f.deleted_at')
                    ->where('parte_f.nome_parte', 'like', $chave);
            });
        });
    }

    private function formatarListagemProduto(array $row): array
    {
        $custos = $this->_custoService->calcularCustosExibicaoGradeProduto(
            (float) ($row['custo_filamento'] ?? 0),
            (string) ($row['tempo_total'] ?? '00:00'),
        );

        return [
            'id'                            => (int) ($row['id'] ?? 0),
            'sku'                           => $row['sku'] ?? '',
            'nome_produto'                  => $row['nome_produto'] ?? '',
            'codigo_base'                   => $row['codigo_base'] ?? null,
            'partes'                        => $row['partes'] ?? '',
            'descricao_combinacao'          => $row['descricao_combinacao'] ?? null,
            'id_grade_produto_combinacao'   => !empty($row['id_grade_produto_combinacao'])
                ? (int) $row['id_grade_produto_combinacao']
                : null,
            'peso_total'                    => (float) ($row['peso_total'] ?? 0),
            'tempo_total'                   => $row['tempo_total'] ?? '00:00',
            'custo_filamento'               => $custos['custo_filamento'],
            'custo_energia'                 => $custos['custo_energia'],
            'custo_desgaste'                => $custos['custo_desgaste'],
            'custo_total'                   => $custos['custo_total'],
            'status'                        => (bool) ($row['status'] ?? true),
            'id_grade_produto'              => (int) ($row['id_grade_produto'] ?? 0),
        ];
    }

    private function formatarDetalheProduto(array $row): array
    {
        $base = $this->formatarListagemProduto($row);

        return array_merge($base, [
            'id_produto_base'  => (int) ($row['id_produto_base'] ?? 0),
            'descricao_grade'  => $row['descricao_grade'] ?? null,
            'created_at'       => $row['created_at'] ?? null,
            'updated_at'       => $row['updated_at'] ?? null,
        ]);
    }

    private function formatarAsyncProduto(array $row): array
    {
        $nome = (string) ($row['nome_produto'] ?? '');
        $sku  = (string) ($row['sku'] ?? '');
        $rotulo = trim($nome . ($sku !== '' ? ' (' . $sku . ')' : ''));

        return [
            'id'           => (int) ($row['id'] ?? 0),
            'descricao'    => $rotulo !== '' ? $rotulo : $sku,
            'sku'          => $sku,
            'nome_produto' => $nome,
            'codigo_base'  => $row['codigo_base'] ?? null,
        ];
    }

    private function montarNomePartesLabel($partes): string
    {
        return $partes
            ->pluck('nome_parte')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->implode(' + ');
    }
}
