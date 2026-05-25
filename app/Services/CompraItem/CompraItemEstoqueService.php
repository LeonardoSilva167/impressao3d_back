<?php

namespace App\Services\CompraItem;

use App\Models\CompraItem;
use App\Repositories\CompraItem\CompraItemRepository;
use App\Services\Estoque\MovimentacaoEstoqueService;
use App\Services\Item\ItemEstoqueRecalculoService;
use Exception;

class CompraItemEstoqueService
{
    /**
     * @var CompraItemRepository $_compraItemRepository
     */
    private CompraItemRepository $_compraItemRepository;

    /**
     * @var ItemEstoqueRecalculoService $_recalculoService
     */
    private ItemEstoqueRecalculoService $_recalculoService;

    /**
     * @var MovimentacaoEstoqueService $_movimentacaoService
     */
    private MovimentacaoEstoqueService $_movimentacaoService;

    public function __construct()
    {
        $this->_compraItemRepository = new CompraItemRepository();
        $this->_recalculoService     = new ItemEstoqueRecalculoService();
        $this->_movimentacaoService  = new MovimentacaoEstoqueService();
    }

    public function aplicarMovimentacao(CompraItem $compraItem, bool $registrarEntrada = false): void
    {
        $this->validarLote($compraItem);

        if ($registrarEntrada) {
            $this->_movimentacaoService->registrarEntradaCompra($compraItem);
        }

        $this->_recalculoService->recalcularItem($compraItem->id_item);
    }

    public function reverterMovimentacao(CompraItem $compraItem): void
    {
        $this->validarReversaoLote($compraItem);

        $this->_compraItemRepository->update($compraItem, ['qtd_atual' => 0]);
        $compraItem->refresh();

        $this->_recalculoService->recalcularItem($compraItem->id_item);
    }

    /**
     * @return array<int, int> IDs dos itens recalculados
     */
    public function cancelarLotesDaCompra(int|string $idCompra): array
    {
        $itens = $this->_compraItemRepository->findByCompraId($idCompra);

        $this->validarLotesIntactosParaCancelamento($itens);

        $itensRecalculados = [];

        foreach ($itens as $compraItem) {
            $qtdAtual = (float) $compraItem->qtd_atual;

            if ($qtdAtual <= 0) {
                continue;
            }

            $this->_movimentacaoService->registrarCancelamentoCompra(
                $compraItem,
                $qtdAtual,
                $qtdAtual,
                0
            );

            $this->_compraItemRepository->update($compraItem, ['qtd_atual' => 0]);
            $itensRecalculados[] = (int) $compraItem->id_item;
        }

        foreach (array_unique($itensRecalculados) as $idItem) {
            $this->_recalculoService->recalcularItem($idItem);
        }

        return array_values(array_unique($itensRecalculados));
    }

    /**
     * @param \Illuminate\Support\Collection<int, CompraItem> $itens
     */
    private function validarLotesIntactosParaCancelamento($itens): void
    {
        foreach ($itens as $compraItem) {
            $qtdOriginal = (float) $compraItem->qtd_original;
            $qtdAtual    = (float) $compraItem->qtd_atual;

            if ($qtdAtual < $qtdOriginal) {
                throw new Exception(
                    'Não é possível cancelar esta compra pois parte do estoque já foi consumido.',
                    422
                );
            }
        }
    }

    private function validarLote(CompraItem $compraItem): void
    {
        if (!$compraItem->id_item) {
            throw new Exception('Lote sem item vinculado.', 422);
        }

        if ((float) $compraItem->qtd_original <= 0) {
            throw new Exception('Lote sem quantidade original.', 422);
        }

        if ((float) $compraItem->qtd_atual < 0) {
            throw new Exception('Quantidade atual do lote não pode ser negativa.', 422);
        }
    }

    private function validarReversaoLote(CompraItem $compraItem): void
    {
        $this->validarLote($compraItem);

        $qtdOriginal = (float) $compraItem->qtd_original;
        $qtdAtual    = (float) $compraItem->qtd_atual;

        if ($qtdAtual < $qtdOriginal) {
            throw new Exception(
                'Não é possível reverter o lote: estoque parcialmente consumido.',
                422
            );
        }
    }
}
