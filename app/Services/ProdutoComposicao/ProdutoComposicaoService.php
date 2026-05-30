<?php

namespace App\Services\ProdutoComposicao;

use App\Models\ProdutoVariacao;
use App\Models\ProjetoImpressao;
use App\Repositories\Filamento\FilamentoRepository;
use App\Repositories\ProdutoBase\ProdutoBaseRepository;
use App\Repositories\ProdutoComposicao\ProdutoComposicaoRepository;
use App\Repositories\ProdutoComposicaoItem\ProdutoComposicaoItemRepository;
use App\Repositories\ProdutoComposicaoVariacao\ProdutoComposicaoVariacaoRepository;
use App\Repositories\ProjetoImpressao\ProjetoImpressaoRepository;
use App\Services\Filamento\FilamentoService;
use App\Services\PaginateService;
use App\Services\ProjetoImpressao\ProjetoImpressaoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProdutoComposicaoService
{
    /**
     * @var ProdutoComposicaoRepository $_repository
     */
    private ProdutoComposicaoRepository $_repository;

    private ProdutoComposicaoVariacaoRepository $_variacaoRepository;

    private ProdutoComposicaoItemRepository $_itemRepository;

    private ProdutoBaseRepository $_produtoRepository;

    private ProjetoImpressaoRepository $_projetoRepository;

    private FilamentoRepository $_filamentoRepository;

    private ProdutoComposicaoCalculoService $_calculoService;

    private ProjetoImpressaoParteItemCalculoService $_itemCalculoService;

    private ProjetoImpressaoService $_projetoService;

    public function __construct()
    {
        $this->_repository          = new ProdutoComposicaoRepository();
        $this->_variacaoRepository  = new ProdutoComposicaoVariacaoRepository();
        $this->_itemRepository      = new ProdutoComposicaoItemRepository();
        $this->_produtoRepository   = new ProdutoBaseRepository();
        $this->_projetoRepository   = new ProjetoImpressaoRepository();
        $this->_filamentoRepository = new FilamentoRepository();
        $this->_calculoService      = new ProdutoComposicaoCalculoService();
        $this->_itemCalculoService  = new ProjetoImpressaoParteItemCalculoService();
        $this->_projetoService      = new ProjetoImpressaoService();
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

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
            'filamentos' => $this->getFilamentosLookup(),
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

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

    // =========================================================
    // CRUD FUNCTIONS
    // =========================================================

    public function createProdutoComposicao(object $atributes): object
    {
        $this->validateComposicaoUnicaPorProduto((int) $atributes->id_produto);
        $this->validateProdutoExiste((int) $atributes->id_produto);
        $this->validateProjetoExiste((int) $atributes->id_projeto_impressao);

        $variacoesProcessadas = $this->processarVariacoesPayload(
            (int) $atributes->id_produto,
            (int) $atributes->id_projeto_impressao,
            $atributes->variacoes
        );

        $composicao = $this->_repository->create([
            'id_produto'           => (int) $atributes->id_produto,
            'id_projeto_impressao' => (int) $atributes->id_projeto_impressao,
        ]);

        $this->persistirVariacoesEItens((int) $composicao->id, $variacoesProcessadas);

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

        $this->validateComposicaoUnicaPorProduto((int) $atributes->id_produto, (int) $atributes->id);
        $this->validateProdutoExiste((int) $atributes->id_produto);
        $this->validateProjetoExiste((int) $atributes->id_projeto_impressao);

        $variacoesProcessadas = $this->processarVariacoesPayload(
            (int) $atributes->id_produto,
            (int) $atributes->id_projeto_impressao,
            $atributes->variacoes
        );

        $saved = $this->_repository->update($record, [
            'id_produto'           => (int) $atributes->id_produto,
            'id_projeto_impressao' => (int) $atributes->id_projeto_impressao,
        ]);

        if (!$saved) {
            throw new Exception('Não foi possível editar a composição do produto', 500);
        }

        $this->removerVariacoesEItens((int) $record->id);
        $this->persistirVariacoesEItens((int) $record->id, $variacoesProcessadas);

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

        $this->removerVariacoesEItens((int) $record->id);

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

    // =========================================================
    // QUERIES
    // =========================================================

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

        $variacoes = $this->_variacaoRepository->getByComposicaoId((int) $record->id)
            ->map(function ($variacao) {
                $itens = $this->_itemRepository->getByVariacaoId((int) $variacao->id)
                    ->map(fn ($item) => $this->mapItemDetalhe($item))
                    ->values()
                    ->toArray();

                return $this->mapVariacaoDetalhe($variacao, $itens);
            })
            ->values()
            ->toArray();

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
            ],
            'variacoes'            => $variacoes,
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

        $variacoes = $this->_produtoRepository->getVariacoesByProdutoBaseId($idProduto, true)
            ->map(fn ($v) => $this->mapVariacaoProduto($v))
            ->values()
            ->toArray();

        $projeto = $this->_projetoService->getProjetoImpressaoId($idProjetoImpressao);
        $projeto = $this->mapProjetoCarregamento($projeto);

        return [
            'produto'    => [
                'id'                => (int) $produto->id,
                'descricao_produto' => $produto->descricao_produto,
                'sku_base'          => $produto->sku_base,
                'variacoes'         => $variacoes,
            ],
            'projeto'    => $projeto,
            'filamentos' => $this->getFilamentosLookup(),
        ];
    }

    // =========================================================
    // HELPERS
    // =========================================================

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

    private function mapProjetoCarregamento(array $projeto): array
    {
        $partes = collect($projeto['partes'] ?? [])
            ->map(function ($parte) {
                $itens = collect($parte['itens'] ?? [])
                    ->map(fn ($item) => $this->mapItemProjetoCarregamento($parte['nome_parte'], $item))
                    ->values()
                    ->toArray();

                return [
                    'id'         => (int) $parte['id'],
                    'nome_parte' => $parte['nome_parte'],
                    'itens'      => $itens,
                ];
            })
            ->values()
            ->toArray();

        return [
            'id'                    => (int) $projeto['id'],
            'nome_original_projeto' => $projeto['nome_original_projeto'],
            'codigo_projeto'        => $projeto['codigo_projeto'],
            'descricao_projeto'     => $projeto['descricao_projeto'] ?? null,
            'partes'                => $partes,
        ];
    }

    private function mapItemProjetoCarregamento(string $nomeParte, array $item): array
    {
        return [
            'id'              => (int) $item['id'],
            'nome_parte'      => $nomeParte,
            'nome_item'       => $item['nome_item'],
            'peso_total'      => (float) ($item['peso_total'] ?? 0),
            'tempo_impressao' => $item['tempo_impressao'],
            'cor'             => [
                'id'          => (int) ($item['id_cor'] ?? 0),
                'descricao'   => $item['cor_descricao'] ?? null,
                'hexadecimal' => $item['cor_hexadecimal'] ?? null,
            ],
        ];
    }

    private function mapVariacaoProduto(object $variacao): array
    {
        return [
            'id'                => (int) $variacao->id,
            'sku'               => $variacao->sku,
            'status'            => $variacao->status,
            'id_cor_primaria'   => (int) $variacao->id_cor_primaria,
            'id_cor_secundaria' => $variacao->id_cor_secundaria !== null ? (int) $variacao->id_cor_secundaria : null,
            'id_cor_terciaria'  => $variacao->id_cor_terciaria !== null ? (int) $variacao->id_cor_terciaria : null,
            'cor_primaria'      => [
                'descricao'   => $variacao->cor_primaria_descricao,
                'codigo'      => $variacao->cor_primaria_codigo,
                'hexadecimal' => $variacao->cor_primaria_hexadecimal ?? null,
            ],
            'cor_secundaria'    => $variacao->id_cor_secundaria !== null ? [
                'descricao'   => $variacao->cor_secundaria_descricao,
                'codigo'      => $variacao->cor_secundaria_codigo,
                'hexadecimal' => $variacao->cor_secundaria_hexadecimal ?? null,
            ] : null,
            'cor_terciaria'     => $variacao->id_cor_terciaria !== null ? [
                'descricao'   => $variacao->cor_terciaria_descricao,
                'codigo'      => $variacao->cor_terciaria_codigo,
                'hexadecimal' => $variacao->cor_terciaria_hexadecimal ?? null,
            ] : null,
        ];
    }

    private function mapVariacaoDetalhe(object $variacao, array $itens): array
    {
        return [
            'id'                     => (int) $variacao->id,
            'id_produto_variacao'    => (int) $variacao->id_produto_variacao,
            'sku'                    => $variacao->sku,
            'status'                 => $variacao->status,
            'custo_total_filamentos' => round((float) $variacao->custo_total_filamentos, 4),
            'tempo_total_impressao'  => $variacao->tempo_total_impressao,
            'cor_primaria'           => [
                'descricao'   => $variacao->cor_primaria_descricao,
                'codigo'      => $variacao->cor_primaria_codigo,
                'hexadecimal' => $variacao->cor_primaria_hexadecimal ?? null,
            ],
            'cor_secundaria'         => $variacao->id_cor_secundaria !== null ? [
                'descricao'   => $variacao->cor_secundaria_descricao,
                'codigo'      => $variacao->cor_secundaria_codigo,
                'hexadecimal' => $variacao->cor_secundaria_hexadecimal ?? null,
            ] : null,
            'cor_terciaria'          => $variacao->id_cor_terciaria !== null ? [
                'descricao'   => $variacao->cor_terciaria_descricao,
                'codigo'      => $variacao->cor_terciaria_codigo,
                'hexadecimal' => $variacao->cor_terciaria_hexadecimal ?? null,
            ] : null,
            'itens'                  => $itens,
        ];
    }

    private function mapItemDetalhe(object $item): array
    {
        return [
            'id'                => (int) $item->id,
            'id_item_projeto'   => (int) $item->id_item_projeto,
            'id_filamento'      => (int) $item->id_filamento,
            'nome_parte'        => $item->nome_parte,
            'nome_item'         => $item->nome_item,
            'peso_total'        => round((float) $item->peso_total, 2),
            'tempo_impressao'   => $item->tempo_impressao,
            'preco_medio_grama' => round((float) $item->preco_medio_grama, 4),
            'custo_item'        => round((float) $item->custo_item, 4),
            'filamento'         => [
                'id'     => (int) $item->id_filamento,
                'resumo' => $item->filamento_resumo,
            ],
            'cor'               => [
                'descricao'   => $item->cor_descricao,
                'hexadecimal' => $item->cor_hexadecimal ?? null,
            ],
        ];
    }

    private function processarVariacoesPayload(int $idProduto, int $idProjeto, array $variacoesPayload): array
    {
        $variacoesAtivas = $this->_produtoRepository
            ->getVariacoesByProdutoBaseId($idProduto, true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->toArray();

        $idsEnviados = collect($variacoesPayload)
            ->pluck('id_produto_variacao')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->toArray();

        if ($variacoesAtivas !== $idsEnviados) {
            throw new Exception('É necessário informar todas as variações ativas do produto.', 422);
        }

        $idsItensProjeto = $this->getIdsItensProjeto($idProjeto);
        $processadas     = [];

        foreach ($variacoesPayload as $variacaoPayload) {
            $variacaoPayload = (object) $variacaoPayload;
            $idVariacao      = (int) $variacaoPayload->id_produto_variacao;

            $this->validateVariacaoPertenceProduto($idProduto, $idVariacao);

            if (empty($variacaoPayload->itens) || !is_array($variacaoPayload->itens)) {
                throw new Exception('Cada variação deve possuir ao menos um item.', 422);
            }

            $itensProcessados = [];
            $idsItensEnviados = [];

            foreach ($variacaoPayload->itens as $itemPayload) {
                $itemPayload = (object) $itemPayload;
                $idItem      = (int) $itemPayload->id_item_projeto;

                if (!in_array($idItem, $idsItensProjeto, true)) {
                    throw new Exception('Item do projeto informado não pertence ao projeto selecionado.', 422);
                }

                if (in_array($idItem, $idsItensEnviados, true)) {
                    throw new Exception('Item do projeto duplicado na variação.', 422);
                }

                $idsItensEnviados[] = $idItem;

                $filamento = $this->_filamentoRepository->findByIdWithRelations((int) $itemPayload->id_filamento);

                if (!$filamento) {
                    throw new Exception('Filamento informado não encontrado.', 422);
                }

                $pesoTotal = $this->_itemCalculoService->normalizarPeso(
                    $itemPayload->peso_total ?? null,
                    'O peso total',
                    true
                );

                $tempoImpressao = (string) $itemPayload->tempo_impressao;

                $precoMedioGrama = isset($itemPayload->preco_medio_grama)
                    ? round((float) $itemPayload->preco_medio_grama, 4)
                    : FilamentoService::resolverPrecoMedioPorGrama(
                        isset($filamento->preco_medio_grama) ? (float) $filamento->preco_medio_grama : null,
                        isset($filamento->item_preco_medio_atual) ? (float) $filamento->item_preco_medio_atual : null,
                        !empty($filamento->id_item) ? (int) $filamento->id_item : null,
                    );

                $custoItem = $this->_calculoService->calcularCustoItem($pesoTotal, $precoMedioGrama);

                $itensProcessados[] = [
                    'id_item_projeto'   => $idItem,
                    'id_filamento'      => (int) $itemPayload->id_filamento,
                    'peso_total'        => $pesoTotal,
                    'tempo_impressao'   => $tempoImpressao,
                    'preco_medio_grama' => $precoMedioGrama,
                    'custo_item'        => $custoItem,
                ];
            }

            sort($idsItensEnviados);

            if ($idsItensEnviados !== $idsItensProjeto) {
                throw new Exception('Cada variação deve possuir todos os itens do projeto de impressão.', 422);
            }

            $custosItens = array_column($itensProcessados, 'custo_item');
            $temposItens = array_column($itensProcessados, 'tempo_impressao');

            $processadas[] = [
                'id_produto_variacao'    => $idVariacao,
                'custo_total_filamentos' => $this->_calculoService->calcularCustoTotal($custosItens),
                'tempo_total_impressao'  => $this->_calculoService->somarTempos($temposItens),
                'itens'                  => $itensProcessados,
            ];
        }

        return $processadas;
    }

    private function persistirVariacoesEItens(int $idComposicao, array $variacoesProcessadas): void
    {
        foreach ($variacoesProcessadas as $variacao) {
            $variacaoSalva = $this->_variacaoRepository->create([
                'id_produto_composicao'  => $idComposicao,
                'id_produto_variacao'    => $variacao['id_produto_variacao'],
                'custo_total_filamentos' => $variacao['custo_total_filamentos'],
                'tempo_total_impressao'  => $variacao['tempo_total_impressao'],
            ]);

            foreach ($variacao['itens'] as $item) {
                $this->_itemRepository->create([
                    'id_produto_composicao_variacao' => (int) $variacaoSalva->id,
                    'id_item_projeto'                => $item['id_item_projeto'],
                    'id_filamento'                   => $item['id_filamento'],
                    'peso_total'                     => $item['peso_total'],
                    'tempo_impressao'                => $item['tempo_impressao'],
                    'preco_medio_grama'              => $item['preco_medio_grama'],
                    'custo_item'                     => $item['custo_item'],
                ]);
            }
        }
    }

    private function removerVariacoesEItens(int $idComposicao): void
    {
        $idsVariacao = DB::table('produto_composicao_variacoes')
            ->where('id_produto_composicao', $idComposicao)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->_itemRepository->deleteByVariacaoIds($idsVariacao);
        $this->_variacaoRepository->deleteByComposicaoId($idComposicao);
    }

    private function getIdsItensProjeto(int $idProjeto): array
    {
        return DB::table('projetos_impressao_parte_itens as item')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'item.id_projeto_impressao_parte')
            ->whereNull('item.deleted_at')
            ->whereNull('parte.deleted_at')
            ->where('parte.id_projeto_impressao', $idProjeto)
            ->orderBy('item.id')
            ->pluck('item.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
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

    private function validateVariacaoPertenceProduto(int $idProduto, int $idVariacao): void
    {
        $variacao = DB::table('produto_variacoes')
            ->where('id', $idVariacao)
            ->whereNull('deleted_at')
            ->first();

        if (!$variacao || (int) $variacao->id_produto_base !== $idProduto) {
            throw new Exception('Variação informada não pertence ao produto selecionado.', 422);
        }

        if ($variacao->status !== ProdutoVariacao::STATUS_ATIVA) {
            throw new Exception('Apenas variações ativas podem compor o produto.', 422);
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
