<?php

namespace App\Services\Item;

use App\Repositories\CompraItem\CompraItemRepository;
use App\Repositories\Item\ItemRepository;
use Exception;

class ItemEstoqueRecalculoService
{
    /**
     * @var ItemRepository $_itemRepository
     */
    private ItemRepository $_itemRepository;

    /**
     * @var CompraItemRepository $_compraItemRepository
     */
    private CompraItemRepository $_compraItemRepository;

    public function __construct()
    {
        $this->_itemRepository       = new ItemRepository();
        $this->_compraItemRepository = new CompraItemRepository();
    }

    public function recalcularItem(int|string $idItem): void
    {
        $item = $this->_itemRepository->findByIdForUpdate($idItem);

        if (!$item) {
            throw new Exception('Item não encontrado', 422);
        }

        $totais  = $this->_compraItemRepository->sumEstoqueEPrecoMedioByItemId($idItem);
        $payload = [];

        if ($item->controla_estoque) {
            $payload['estoque_atual'] = round($totais['estoque'], 4);
        }

        if ($item->gera_custo) {
            $payload['preco_medio_atual'] = round($totais['preco_medio'], 4);
        }

        if (!empty($payload)) {
            $this->_itemRepository->update($item, $payload);
        }
    }

    /**
     * Débito de estoque por FIFO — consumir primeiro o lote mais antigo.
     * Uso futuro: vendas, composição de produtos, consumo interno.
     */
    public function debitarEstoqueFifo(int|string $idItem, float $quantidade): void
    {
        if ($quantidade <= 0) {
            throw new Exception('Quantidade para débito deve ser maior que zero.', 422);
        }

        $item = $this->_itemRepository->findByIdForUpdate($idItem);

        if (!$item) {
            throw new Exception('Item não encontrado', 422);
        }

        if (!$item->controla_estoque) {
            return;
        }

        $restante = round($quantidade, 4);
        $lotes    = $this->_compraItemRepository->findLotesComEstoqueByItemIdOrderedFifo($idItem);

        foreach ($lotes as $lote) {
            if ($restante <= 0) {
                break;
            }

            $qtdAtual = (float) $lote->qtd_atual;
            $debito   = min($qtdAtual, $restante);
            $novaQtd  = round($qtdAtual - $debito, 4);

            if ($novaQtd < 0) {
                throw new Exception('Quantidade atual do lote não pode ser negativa.', 422);
            }

            $this->_compraItemRepository->update($lote, ['qtd_atual' => $novaQtd]);
            $restante = round($restante - $debito, 4);
        }

        if ($restante > 0) {
            throw new Exception(
                'Estoque insuficiente para o item ' . $item->descricao,
                422
            );
        }

        $this->recalcularItem($idItem);
    }
}
