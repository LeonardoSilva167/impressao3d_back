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
