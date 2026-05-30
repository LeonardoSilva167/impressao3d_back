<?php

namespace App\Services\GradeProduto;

use App\Models\ProdutoBase;
use App\Repositories\GradeProduto\GradeProdutoRepository;
use App\Repositories\GradeProdutoCombinacao\GradeProdutoCombinacaoRepository;
use App\Repositories\GradeProdutoCombinacaoParte\GradeProdutoCombinacaoParteRepository;
use App\Repositories\GradeProdutoItem\GradeProdutoItemRepository;
use App\Repositories\ProdutoComposicao\ProdutoComposicaoRepository;
use App\Repositories\ProdutoVariacao\ProdutoVariacaoRepository;
use App\Services\PaginateService;
use App\Services\ProdutoComposicao\ProdutoComposicaoVariacaoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;
use Exception;
use Illuminate\Support\Facades\DB;

class GradeProdutoService
{
    private GradeProdutoRepository $_repository;

    private GradeProdutoCombinacaoRepository $_combinacaoRepository;

    private GradeProdutoCombinacaoParteRepository $_combinacaoParteRepository;

    private GradeProdutoItemRepository $_itemRepository;

    private ProdutoComposicaoRepository $_composicaoRepository;

    private ProdutoVariacaoRepository $_variacaoRepository;

    private GradeProdutoGeracaoService $_geracaoService;

    private ProdutoComposicaoVariacaoService $_variacaoService;

    private ProjetoImpressaoParteItemCalculoService $_itemCalculoService;

    public function __construct()
    {
        $this->_repository                 = new GradeProdutoRepository();
        $this->_combinacaoRepository       = new GradeProdutoCombinacaoRepository();
        $this->_combinacaoParteRepository  = new GradeProdutoCombinacaoParteRepository();
        $this->_itemRepository             = new GradeProdutoItemRepository();
        $this->_composicaoRepository       = new ProdutoComposicaoRepository();
        $this->_variacaoRepository         = new ProdutoVariacaoRepository();
        $this->_geracaoService             = new GradeProdutoGeracaoService();
        $this->_variacaoService            = new ProdutoComposicaoVariacaoService();
        $this->_itemCalculoService         = new ProjetoImpressaoParteItemCalculoService();
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
                'data'    => $this->getGradeProdutoId($grade->id),
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
            'data'    => $this->getGradeProdutoId($grade->id),
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
            'data'    => $this->getGradeProdutoId($record->id),
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

    public function getGradeProdutoId(int|string $id): array
    {
        $record = $this->_repository->findByIdWithRelations($id);

        if (!$record) {
            throw new Exception('Grade de produtos não encontrada', 404);
        }

        $composicao = $this->_composicaoRepository->findAtivaByProdutoId((int) $record->id_produto_base);

        $composicaoCompleta = $composicao
            ? $this->montarComposicaoCompleta((int) $composicao->id)
            : null;

        return [
            'id'              => (int) $record->id,
            'id_produto_base' => (int) $record->id_produto_base,
            'descricao'       => $record->descricao,
            'status'          => (bool) $record->status,
            'created_at'      => $record->created_at,
            'updated_at'      => $record->updated_at,
            'produto'         => [
                'descricao_produto' => $record->descricao_produto,
                'sku_base'          => $record->sku_base,
                'codigo_base'       => $record->codigo_base,
            ],
            'combinacoes'      => $this->montarCombinacoesResponse((int) $record->id),
            'composicao'       => $composicaoCompleta,
            'produtos_gerados' => $this->_itemRepository->getByGradeId((int) $record->id)->toArray(),
            'quantidade_produtos' => $this->_itemRepository->countByGradeId((int) $record->id),
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
        $query = $this->_repository->getAsyncQuery();

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('gp.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('pb.descricao_produto', 'like', '%' . $chave . '%')
                    ->orWhere('pb.sku_base', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
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
                return !empty($variacao->id_filamento) && !empty($variacao->custo_total);
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
        }

        return $combinacoes;
    }

    private function removerCombinacoesGrade(int $idGrade): void
    {
        $combinacoes = $this->_combinacaoRepository->getByGradeId($idGrade);
        $ids = $combinacoes->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        $this->_combinacaoParteRepository->deleteByCombinacaoIds($ids);
        $this->_combinacaoRepository->deleteByGradeId($idGrade);
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
                'id_grade_produto' => $idGrade,
                'nome_produto'     => $produto['nome_produto'],
                'sku'              => $produto['sku'],
                'peso_total'       => $produto['peso_total'],
                'tempo_total'      => $produto['tempo_total'],
                'custo_filamento'  => $produto['custo_filamento'],
                'custo_energia'    => $produto['custo_energia'],
                'custo_desgaste'   => $produto['custo_desgaste'],
                'custo_total'      => $produto['custo_total'],
                'status'           => true,
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

    private function applyFiltros($query, object $atributes): void
    {
        if (!empty($atributes->id_produto_base)) {
            $query->where('gp.id_produto_base', $atributes->id_produto_base);
        }

        if (!empty($atributes->descricao)) {
            $chave = $atributes->descricao;
            $query->where('gp.descricao', 'like', '%' . $chave . '%');
        }

        if (isset($atributes->status) && $atributes->status !== '') {
            $query->where('gp.status', $this->normalizarStatus($atributes->status));
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('gp.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('pb.descricao_produto', 'like', '%' . $chave . '%')
                    ->orWhere('pb.sku_base', 'like', '%' . $chave . '%');
            });
        }
    }
}
