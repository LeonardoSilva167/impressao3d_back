<?php

namespace App\Services\Estoque;

use App\Models\CompraItem;
use App\Models\Filamento;
use App\Models\MovimentacaoEstoque;
use App\Repositories\CompraItem\CompraItemRepository;
use App\Repositories\MovimentacaoEstoque\MovimentacaoEstoqueRepository;
use App\Services\Item\ItemEstoqueRecalculoService;
use Exception;
use Illuminate\Support\Facades\DB;

class MovimentacaoEstoqueService
{
    /**
     * @var MovimentacaoEstoqueRepository $_repository
     */
    private MovimentacaoEstoqueRepository $_repository;

    /**
     * @var CompraItemRepository $_compraItemRepository
     */
    private CompraItemRepository $_compraItemRepository;

    /**
     * @var ItemEstoqueRecalculoService $_recalculoService
     */
    private ItemEstoqueRecalculoService $_recalculoService;

    public function __construct()
    {
        $this->_repository             = new MovimentacaoEstoqueRepository();
        $this->_compraItemRepository   = new CompraItemRepository();
        $this->_recalculoService       = new ItemEstoqueRecalculoService();
    }

    // =========================================================
    // HANDLE FUNCTIONS (orquestração + transação)
    // =========================================================

    public function handleConsumirFilamento(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result = $this->consumirFilamento($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function handleFinalizarCarretel(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result = $this->finalizarCarretel($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    // =========================================================
    // MOVIMENTAÇÕES
    // =========================================================

    public function registrarEntradaCompra(CompraItem $compraItem): MovimentacaoEstoque
    {
        return $this->_repository->create([
            'id_item'            => $compraItem->id_item,
            'tipo_movimentacao'  => MovimentacaoEstoque::TIPO_ENTRADA_COMPRA,
            'qtd'                => $compraItem->qtd_original,
            'observacao'         => 'Entrada via compra #' . $compraItem->id_compra,
            'data_movimentacao'  => now(),
        ]);
    }

    public function registrarCancelamentoCompra(
        CompraItem $compraItem,
        float $quantidadeRemovida,
        float $saldoAnterior,
        float $saldoPosterior
    ): MovimentacaoEstoque {
        return $this->_repository->create([
            'id_item'           => $compraItem->id_item,
            'id_compra_item'    => $compraItem->id,
            'tipo_movimentacao' => MovimentacaoEstoque::TIPO_CANCELAMENTO_COMPRA,
            'qtd'               => $quantidadeRemovida,
            'saldo_anterior'    => $saldoAnterior,
            'saldo_posterior'   => $saldoPosterior,
            'observacao'        => 'Cancelamento da compra #' . $compraItem->id_compra,
            'data_movimentacao' => now(),
        ]);
    }

    public function consumirFilamento(object $atributes): object
    {
        $filamento = $this->resolveFilamento((int) $atributes->id_filamento);
        $idItem    = (int) $filamento->id_item;
        $quantidade = round((float) $atributes->qtd, 4);
        $tipo       = $atributes->tipo_movimentacao ?? MovimentacaoEstoque::TIPO_CONSUMO_TESTE;

        $this->validarTipoSaida($tipo);

        if ($quantidade <= 0) {
            throw new Exception('A quantidade consumida deve ser maior que zero.', 422);
        }

        $this->_recalculoService->debitarEstoqueFifo($idItem, $quantidade);

        $movimentacao = $this->_repository->create([
            'id_item'           => $idItem,
            'tipo_movimentacao' => $tipo,
            'qtd'               => $quantidade,
            'observacao'        => $atributes->observacao ?? null,
            'data_movimentacao' => now(),
        ]);

        return (object) [
            'data'    => $movimentacao,
            'status'  => true,
            'message' => 'Consumo registrado com sucesso!',
        ];
    }

    public function finalizarCarretel(object $atributes): object
    {
        $filamento = $this->resolveFilamento((int) $atributes->id_filamento);
        $idItem    = (int) $filamento->id_item;
        $gramatura = $this->resolverGramaturaPadrao($idItem);

        $this->_recalculoService->debitarEstoqueFifo($idItem, $gramatura);

        $movimentacao = $this->_repository->create([
            'id_item'           => $idItem,
            'tipo_movimentacao' => MovimentacaoEstoque::TIPO_DESCARTE,
            'qtd'               => $gramatura,
            'observacao'        => $atributes->observacao ?? 'Finalização de carretel (' . $gramatura . 'g)',
            'data_movimentacao' => now(),
        ]);

        return (object) [
            'data'    => [
                'movimentacao' => $movimentacao,
                'gramatura'    => $gramatura,
            ],
            'status'  => true,
            'message' => 'Carretel finalizado com sucesso!',
        ];
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function resolveFilamento(int $idFilamento): Filamento
    {
        $filamento = Filamento::where('id', $idFilamento)
            ->whereNull('deleted_at')
            ->first();

        if (!$filamento) {
            throw new Exception('Filamento não encontrado', 404);
        }

        if (!$filamento->id_item) {
            throw new Exception('Filamento sem item vinculado.', 422);
        }

        return $filamento;
    }

    private function resolverGramaturaPadrao(int $idItem): float
    {
        $lote = $this->_compraItemRepository->findLoteMaisAntigoComEstoque($idItem);

        if (!$lote) {
            throw new Exception('Não há lotes com saldo disponível para este item.', 422);
        }

        $gramatura = $lote->gramatura_filamento;

        if ($gramatura === null || !in_array((int) $gramatura, [500, 1000], true)) {
            throw new Exception(
                'Não foi possível identificar a gramatura padrão do lote mais antigo. Informe a gramatura na compra (500 ou 1000).',
                422
            );
        }

        return (float) $gramatura;
    }

    private function validarTipoSaida(string $tipo): void
    {
        if (!in_array($tipo, MovimentacaoEstoque::TIPOS_SAIDA, true)) {
            throw new Exception('Tipo de movimentação inválido para saída de estoque.', 422);
        }
    }
}
