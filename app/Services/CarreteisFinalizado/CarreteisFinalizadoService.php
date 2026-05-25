<?php

namespace App\Services\CarreteisFinalizado;

use App\Models\CarreteisFinalizado;
use App\Models\Filamento;
use App\Models\Item;
use App\Models\MovimentacaoEstoque;
use App\Repositories\CarreteisFinalizado\CarreteisFinalizadoRepository;
use App\Repositories\Item\ItemRepository;
use App\Repositories\MovimentacaoEstoque\MovimentacaoEstoqueRepository;
use App\Services\Item\ItemEstoqueRecalculoService;
use App\Services\PaginateService;
use Exception;
use Illuminate\Support\Facades\DB;

class CarreteisFinalizadoService
{
    /**
     * @var CarreteisFinalizadoRepository $_repository
     */
    private CarreteisFinalizadoRepository $_repository;

    /**
     * @var ItemRepository $_itemRepository
     */
    private ItemRepository $_itemRepository;

    /**
     * @var MovimentacaoEstoqueRepository $_movimentacaoRepository
     */
    private MovimentacaoEstoqueRepository $_movimentacaoRepository;

    /**
     * @var ItemEstoqueRecalculoService $_recalculoService
     */
    private ItemEstoqueRecalculoService $_recalculoService;

    public function __construct()
    {
        $this->_repository             = new CarreteisFinalizadoRepository();
        $this->_itemRepository         = new ItemRepository();
        $this->_movimentacaoRepository = new MovimentacaoEstoqueRepository();
        $this->_recalculoService       = new ItemEstoqueRecalculoService();
    }

    // =========================================================
    // LOOKUPS
    // =========================================================

    public function handleLookupsCarreteisFinalizado(): array
    {
        $filamentos = DB::table('filamentos as fil')
            ->join('itens as item', 'item.id', '=', 'fil.id_item')
            ->whereNull('fil.deleted_at')
            ->whereNull('item.deleted_at')
            ->where('item.ativo', true)
            ->select(
                'fil.id',
                'fil.id_item',
                'fil.codigo',
                'fil.resumo',
                'item.descricao as item_descricao',
                'item.codigo as item_codigo',
                'item.estoque_atual',
            )
            ->orderBy('fil.resumo')
            ->orderBy('fil.codigo')
            ->get()
            ->map(fn ($row) => [
                'id'            => $row->id,
                'id_item'       => $row->id_item,
                'codigo'        => $row->codigo,
                'resumo'        => $row->resumo,
                'estoque_atual' => $row->estoque_atual,
                'item'          => [
                    'id'        => $row->id_item,
                    'descricao' => $row->item_descricao,
                    'codigo'    => $row->item_codigo,
                ],
            ])
            ->values()
            ->all();

        return [
            'gramaturas' => CarreteisFinalizado::GRAMATURAS_VALIDAS,
            'filamentos' => $filamentos,
        ];
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleAddCarreteisFinalizado(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result = $this->createCarreteisFinalizado($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleEditCarreteisFinalizado(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result = $this->updateCarreteisFinalizado($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleDeleteCarreteisFinalizado(int|string $id): object
    {
        try {
            DB::beginTransaction();

            $result = $this->deleteCarreteisFinalizado($id);

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

    public function createCarreteisFinalizado(object $atributes): object
    {
        $item       = $this->resolveItem((int) $atributes->id_item);
        $gramatura  = (int) $atributes->gramatura;
        $quantidade = (int) $atributes->quantidade;

        $this->validarGramatura($gramatura);
        $this->validarQuantidade($quantidade);

        $qtdTotalConsumida = round($quantidade * $gramatura, 4);
        $dataFinalizacao   = $atributes->data_finalizacao ?? now();

        $registro = $this->_repository->create([
            'id_item'             => $item->id,
            'gramatura'           => $gramatura,
            'quantidade'          => $quantidade,
            'qtd_total_consumida' => $qtdTotalConsumida,
            'observacao'          => $atributes->observacao ?? null,
            'data_finalizacao'    => $dataFinalizacao,
        ]);

        $movimentacoes = $this->aplicarConsumoEstoque(
            $registro,
            $item->id,
            $qtdTotalConsumida,
            $gramatura,
            $dataFinalizacao,
            $atributes->observacao ?? null
        );

        return (object) [
            'data'    => [
                'carreteis_finalizados' => $registro->fresh(),
                'movimentacoes'         => $movimentacoes,
            ],
            'status'  => true,
            'message' => 'Carretéis finalizados registrados com sucesso!',
        ];
    }

    public function updateCarreteisFinalizado(object $atributes): object
    {
        $registro = $this->buscarRegistroParaEscrita((int) $atributes->id);
        $item     = $this->resolveItem((int) $atributes->id_item);
        $gramatura  = (int) $atributes->gramatura;
        $quantidade = (int) $atributes->quantidade;

        $this->validarGramatura($gramatura);
        $this->validarQuantidade($quantidade);

        $qtdTotalConsumida = round($quantidade * $gramatura, 4);
        $dataFinalizacao     = $atributes->data_finalizacao ?? $registro->data_finalizacao;
        $observacao          = $atributes->observacao ?? null;

        $consumoAlterado = (int) $registro->id_item !== (int) $item->id
            || (int) $registro->gramatura !== $gramatura
            || (int) $registro->quantidade !== $quantidade
            || round((float) $registro->qtd_total_consumida, 4) !== $qtdTotalConsumida;

        $movimentacoesEstorno = [];
        $movimentacoesDebito  = [];

        if ($consumoAlterado) {
            $movimentacoesEstorno = $this->reverterConsumoEstoque($registro);
        }

        $this->_repository->update($registro, [
            'id_item'             => $item->id,
            'gramatura'           => $gramatura,
            'quantidade'          => $quantidade,
            'qtd_total_consumida' => $qtdTotalConsumida,
            'observacao'          => $observacao,
            'data_finalizacao'    => $dataFinalizacao,
        ]);

        $registro->refresh();

        if ($consumoAlterado) {
            $movimentacoesDebito = $this->aplicarConsumoEstoque(
                $registro,
                $item->id,
                $qtdTotalConsumida,
                $gramatura,
                $dataFinalizacao,
                $observacao
            );
        } else {
            $this->atualizarMetadadosMovimentacoes($registro, $observacao, $dataFinalizacao);
        }

        return (object) [
            'data'    => [
                'carreteis_finalizados' => $registro->fresh(),
                'movimentacoes_estorno' => $movimentacoesEstorno,
                'movimentacoes'         => $movimentacoesDebito,
            ],
            'status'  => true,
            'message' => 'Carretéis finalizados alterados com sucesso!',
        ];
    }

    public function deleteCarreteisFinalizado(int|string $id): object
    {
        $registro = $this->buscarRegistroParaEscrita($id);
        $movimentacoesEstorno = $this->reverterConsumoEstoque($registro);

        $this->_repository->delete($registro);

        return (object) [
            'data'    => [
                'movimentacoes_estorno' => $movimentacoesEstorno,
            ],
            'status'  => true,
            'message' => 'Carretéis finalizados excluídos com sucesso!',
        ];
    }

    // =========================================================
    // QUERIES
    // =========================================================

    public function getCarreteisFinalizadosPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.id_item',
            'ent.gramatura',
            'ent.quantidade',
            'ent.qtd_total_consumida',
            'ent.observacao',
            'ent.data_finalizacao',
            'ent.created_at',
            'fil.id as id_filamento',
            'fil.codigo as filamento_codigo',
            'fil.resumo as filamento_resumo',
            'item.descricao as item_descricao',
            'item.codigo as item_codigo',
        );

        $query->from('carreteis_finalizados as ent');
        $query->join('itens as item', 'item.id', '=', 'ent.id_item');
        $query->leftJoin('filamentos as fil', function ($join) {
            $join->on('fil.id_item', '=', 'ent.id_item')
                ->whereNull('fil.deleted_at');
        });
        $query->whereNull('ent.deleted_at');
        $query->whereNull('item.deleted_at');
        $query->orderByDesc('ent.data_finalizacao');
        $query->orderByDesc('ent.id');

        if (!empty($atributes->id_item)) {
            $query->where('ent.id_item', $atributes->id_item);
        }

        if (!empty($atributes->id_filamento)) {
            $query->where('fil.id', $atributes->id_filamento);
        }

        if (!empty($atributes->data_finalizacao_inicio)) {
            $query->whereDate('ent.data_finalizacao', '>=', $atributes->data_finalizacao_inicio);
        }

        if (!empty($atributes->data_finalizacao_fim)) {
            $query->whereDate('ent.data_finalizacao', '<=', $atributes->data_finalizacao_fim);
        }

        if (!empty($atributes->gramatura)) {
            $query->where('ent.gramatura', (int) $atributes->gramatura);
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('item.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('item.codigo', 'like', '%' . $chave . '%')
                    ->orWhere('fil.resumo', 'like', '%' . $chave . '%')
                    ->orWhere('fil.codigo', 'like', '%' . $chave . '%')
                    ->orWhere('ent.observacao', 'like', '%' . $chave . '%');
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

        $payload = collect($resultado)->toArray();
        $payload['data'] = array_map(
            fn ($row) => $this->formatarListagem((array) $row),
            $payload['data'] ?? []
        );

        return $payload;
    }

    public function getCarreteisFinalizadoId(int|string $id): object
    {
        $registro = $this->buscarRegistroDetalhado($id);

        if (!$registro) {
            throw new Exception('Registro de carretéis finalizados não encontrado', 404);
        }

        return (object) [
            'data' => $this->formatarDetalhe($registro),
        ];
    }

    /**
     * @return array<int, array{
     *     id_compra_item: int,
     *     compra: string,
     *     plataforma: string|null,
     *     data_compra: mixed,
     *     saldo_atual: float,
     *     qtd_consumida: float,
     *     saldo_restante: float,
     *     valor_unitario_real: float
     * }>
     */
    public function getLotesConsumo(int $idItem, int $quantidade, int $gramatura): array
    {
        $item = $this->resolveItem($idItem);

        return $this->simularConsumoFifo($item->id, $quantidade, $gramatura);
    }

    public function getCarreteisFinalizadoAsync(object $params): array
    {
        $query = DB::table('filamentos as fil')
            ->join('itens as item', 'item.id', '=', 'fil.id_item')
            ->whereNull('fil.deleted_at')
            ->whereNull('item.deleted_at')
            ->select(
                'fil.id',
                'fil.id_item',
                'fil.codigo',
                'fil.resumo',
                'item.estoque_atual',
            )
            ->orderBy('fil.resumo');

        if (!empty($params->palavra_chave)) {
            $chave = $params->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('fil.resumo', 'like', '%' . $chave . '%')
                    ->orWhere('fil.codigo', 'like', '%' . $chave . '%')
                    ->orWhere('item.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('item.codigo', 'like', '%' . $chave . '%');
            });
            $query->limit(10);
        }

        return $query->get()->toArray();
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * @return array<int, array{
     *     id_compra_item: int,
     *     compra: string,
     *     plataforma: string|null,
     *     data_compra: mixed,
     *     saldo_atual: float,
     *     qtd_consumida: float,
     *     saldo_restante: float,
     *     valor_unitario_real: float
     * }>
     */
    private function simularConsumoFifo(int $idItem, int $quantidade, int $gramatura): array
    {
        $this->validarGramatura($gramatura);
        $this->validarQuantidade($quantidade);

        $qtdTotalConsumida = round($quantidade * $gramatura, 4);
        $lotesSimulados    = $this->_recalculoService->simularDebitoEstoqueFifo(
            $idItem,
            $qtdTotalConsumida
        );

        if (empty($lotesSimulados)) {
            throw new Exception('Não há lotes com saldo disponível para este item.', 422);
        }

        return array_map(
            fn (array $lote) => $this->formatarLoteConsumoFifo($lote),
            $lotesSimulados
        );
    }

    private function resolveItem(int $idItem): Item
    {
        $item = $this->_itemRepository->findById($idItem);

        if (!$item) {
            throw new Exception('Item não encontrado', 404);
        }

        return $item;
    }

    private function validarGramatura(int $gramatura): void
    {
        if (!in_array($gramatura, CarreteisFinalizado::GRAMATURAS_VALIDAS, true)) {
            throw new Exception('Gramatura inválida. Informe 500 ou 1000.', 422);
        }
    }

    private function validarQuantidade(int $quantidade): void
    {
        if ($quantidade <= 0) {
            throw new Exception('A quantidade deve ser maior que zero.', 422);
        }
    }

    private function buscarRegistroDetalhado(int|string $id): ?CarreteisFinalizado
    {
        return CarreteisFinalizado::query()
            ->whereNull('deleted_at')
            ->with([
                'item.filamento',
                'movimentacoes.compraItem.compra.plataformaCompra',
            ])
            ->find($id);
    }

    private function buscarRegistroParaEscrita(int|string $id): CarreteisFinalizado
    {
        $registro = CarreteisFinalizado::query()
            ->whereNull('deleted_at')
            ->with(['movimentacoes'])
            ->find($id);

        if (!$registro) {
            throw new Exception('Registro de carretéis finalizados não encontrado', 404);
        }

        return $registro;
    }

    /**
     * @return array<int, MovimentacaoEstoque>
     */
    private function aplicarConsumoEstoque(
        CarreteisFinalizado $registro,
        int $idItem,
        float $qtdTotalConsumida,
        int $gramatura,
        $dataFinalizacao,
        ?string $observacao
    ): array {
        $detalhesDebito = $this->_recalculoService->debitarEstoqueFifoComDetalhes(
            $idItem,
            $qtdTotalConsumida
        );

        $movimentacoes = [];

        foreach ($detalhesDebito as $detalhe) {
            $movimentacoes[] = $this->_movimentacaoRepository->create([
                'id_item'                  => $idItem,
                'id_compra_item'           => $detalhe['id_compra_item'],
                'tipo_movimentacao'        => MovimentacaoEstoque::TIPO_FINALIZACAO_CARRETEL,
                'qtd'                      => $detalhe['debito'],
                'gramatura'                => $gramatura,
                'saldo_anterior'           => $detalhe['saldo_anterior'],
                'saldo_posterior'          => $detalhe['saldo_posterior'],
                'id_carreteis_finalizados' => $registro->id,
                'observacao'               => $observacao,
                'data_movimentacao'        => $dataFinalizacao,
            ]);
        }

        return $movimentacoes;
    }

    /**
     * @return array<int, MovimentacaoEstoque>
     */
    private function reverterConsumoEstoque(CarreteisFinalizado $registro): array
    {
        $creditosPorItem = $this->calcularCreditosLiquidosPorItem($registro);

        if (empty($creditosPorItem)) {
            $this->_recalculoService->recalcularItem($registro->id_item);
            return [];
        }

        $movimentacoesEstorno = [];

        foreach ($creditosPorItem as $idItem => $creditos) {
            $detalhesCredito = $this->_recalculoService->creditarEstoqueNosLotes(
                $idItem,
                array_map(
                    fn (array $credito) => [
                        'id_compra_item' => $credito['id_compra_item'],
                        'quantidade'     => $credito['quantidade'],
                    ],
                    $creditos
                )
            );

            foreach ($creditos as $index => $credito) {
                $detalhe = $detalhesCredito[$index] ?? null;

                if (!$detalhe) {
                    continue;
                }

                $movimentacoesEstorno[] = $this->_movimentacaoRepository->create([
                    'id_item'                  => (int) $idItem,
                    'id_compra_item'           => $detalhe['id_compra_item'],
                    'tipo_movimentacao'        => MovimentacaoEstoque::TIPO_ESTORNO_FINALIZACAO_CARRETEL,
                    'qtd'                      => $detalhe['credito'],
                    'gramatura'                => $credito['gramatura'],
                    'saldo_anterior'           => $detalhe['saldo_anterior'],
                    'saldo_posterior'          => $detalhe['saldo_posterior'],
                    'id_carreteis_finalizados' => $registro->id,
                    'observacao'               => 'Estorno finalização carretel #' . $registro->id,
                    'data_movimentacao'        => now(),
                ]);
            }
        }

        return $movimentacoesEstorno;
    }

    /**
     * Calcula créditos líquidos a estornar com base nas movimentações ativas do registro.
     *
     * @return array<int, array<int, array{id_compra_item: int, quantidade: float, gramatura: int|null}>>
     */
    private function calcularCreditosLiquidosPorItem(CarreteisFinalizado $registro): array
    {
        $saldoPorLote = [];

        foreach ($registro->movimentacoes as $movimentacao) {
            if (!$movimentacao->id_compra_item) {
                continue;
            }

            $chave = $movimentacao->id_item . ':' . $movimentacao->id_compra_item;

            if (!isset($saldoPorLote[$chave])) {
                $saldoPorLote[$chave] = [
                    'id_item'         => (int) $movimentacao->id_item,
                    'id_compra_item'  => (int) $movimentacao->id_compra_item,
                    'quantidade'      => 0.0,
                    'gramatura'       => $movimentacao->gramatura,
                ];
            }

            $qtd = (float) $movimentacao->qtd;

            if ($movimentacao->tipo_movimentacao === MovimentacaoEstoque::TIPO_FINALIZACAO_CARRETEL) {
                $saldoPorLote[$chave]['quantidade'] = round($saldoPorLote[$chave]['quantidade'] + $qtd, 4);
                $saldoPorLote[$chave]['gramatura']  = $movimentacao->gramatura;
            }

            if ($movimentacao->tipo_movimentacao === MovimentacaoEstoque::TIPO_ESTORNO_FINALIZACAO_CARRETEL) {
                $saldoPorLote[$chave]['quantidade'] = round($saldoPorLote[$chave]['quantidade'] - $qtd, 4);
            }
        }

        $creditosPorItem = [];

        foreach ($saldoPorLote as $saldo) {
            if ($saldo['quantidade'] <= 0) {
                continue;
            }

            $creditosPorItem[$saldo['id_item']][] = [
                'id_compra_item' => $saldo['id_compra_item'],
                'quantidade'     => $saldo['quantidade'],
                'gramatura'      => $saldo['gramatura'],
            ];
        }

        return $creditosPorItem;
    }

    private function atualizarMetadadosMovimentacoes(
        CarreteisFinalizado $registro,
        ?string $observacao,
        $dataFinalizacao
    ): void {
        foreach ($registro->movimentacoes as $movimentacao) {
            if ($movimentacao->tipo_movimentacao !== MovimentacaoEstoque::TIPO_FINALIZACAO_CARRETEL) {
                continue;
            }

            $movimentacao->update([
                'observacao'        => $observacao,
                'data_movimentacao' => $dataFinalizacao,
            ]);
        }
    }

    private function formatarListagem(array $row): array
    {
        return [
            'id'                  => $row['id'],
            'filamento'           => !empty($row['id_filamento']) ? [
                'id'     => $row['id_filamento'],
                'codigo' => $row['filamento_codigo'],
                'resumo' => $row['filamento_resumo'],
            ] : null,
            'item'                => [
                'id'        => $row['id_item'],
                'descricao' => $row['item_descricao'],
                'codigo'    => $row['item_codigo'],
            ],
            'gramatura'           => $row['gramatura'],
            'quantidade'          => $row['quantidade'],
            'qtd_total_consumida' => $row['qtd_total_consumida'],
            'data_finalizacao'    => $row['data_finalizacao'],
            'usuario'             => null,
            'observacao'          => $row['observacao'],
            'created_at'          => $row['created_at'],
        ];
    }

    private function formatarDetalhe(CarreteisFinalizado $registro): array
    {
        $filamento = $registro->item?->filamento;

        return [
            'id'                  => $registro->id,
            'filamento'           => $filamento ? [
                'id'     => $filamento->id,
                'codigo' => $filamento->codigo,
                'resumo' => $filamento->resumo,
            ] : null,
            'item'                => [
                'id'        => $registro->id_item,
                'descricao' => $registro->item?->descricao,
                'codigo'    => $registro->item?->codigo,
            ],
            'gramatura'           => $registro->gramatura,
            'quantidade'          => $registro->quantidade,
            'qtd_total_consumida' => $registro->qtd_total_consumida,
            'data_finalizacao'    => $registro->data_finalizacao,
            'usuario'             => null,
            'observacao'          => $registro->observacao,
            'movimentacoes'       => $registro->movimentacoes->map(fn ($mov) => [
                'id'              => $mov->id,
                'tipo_movimentacao' => $mov->tipo_movimentacao,
                'qtd'             => $mov->qtd,
                'gramatura'       => $mov->gramatura,
                'saldo_anterior'  => $mov->saldo_anterior,
                'saldo_posterior' => $mov->saldo_posterior,
                'lote'            => $mov->compraItem ? [
                    'id'                  => $mov->compraItem->id,
                    'qtd_original'        => $mov->compraItem->qtd_original,
                    'qtd_atual'           => $mov->compraItem->qtd_atual,
                    'valor_unitario_real' => $mov->compraItem->valor_unitario_real,
                    'compra'              => $mov->compraItem->compra ? [
                        'id'            => $mov->compraItem->compra->id,
                        'numero_pedido' => $mov->compraItem->compra->numero_pedido,
                        'data_compra'   => $mov->compraItem->compra->data_compra,
                        'plataforma'    => $mov->compraItem->compra->plataformaCompra ? [
                            'id'        => $mov->compraItem->compra->plataformaCompra->id,
                            'descricao' => $mov->compraItem->compra->plataformaCompra->descricao,
                        ] : null,
                    ] : null,
                ] : null,
            ])->values()->all(),
            'created_at'          => $registro->created_at,
        ];
    }

    private function formatarLoteConsumoFifo(array $lote): array
    {
        return [
            'id_compra_item'      => $lote['id_compra_item'],
            'compra'              => (string) $lote['id_compra'],
            'plataforma'          => $lote['plataforma'],
            'data_compra'         => $lote['data_compra'],
            'saldo_atual'         => $lote['saldo_atual'],
            'qtd_consumida'       => $lote['qtd_consumida'],
            'saldo_restante'      => $lote['saldo_restante'],
            'valor_unitario_real' => $lote['valor_unitario_real'],
        ];
    }
}
