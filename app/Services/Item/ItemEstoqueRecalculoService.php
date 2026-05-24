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

    /**
     * Débito FIFO com detalhamento por lote — retorna saldos anterior/posterior de cada lote afetado.
     *
     * @return array<int, array{id_compra_item: int, debito: float, saldo_anterior: float, saldo_posterior: float}>
     */
    public function debitarEstoqueFifoComDetalhes(int|string $idItem, float $quantidade): array
    {
        if ($quantidade <= 0) {
            throw new Exception('Quantidade para débito deve ser maior que zero.', 422);
        }

        $item = $this->_itemRepository->findByIdForUpdate($idItem);

        if (!$item) {
            throw new Exception('Item não encontrado', 422);
        }

        if (!$item->controla_estoque) {
            return [];
        }

        $restante = round($quantidade, 4);
        $lotes    = $this->_compraItemRepository->findLotesComEstoqueByItemIdOrderedFifo($idItem);
        $detalhes = [];

        foreach ($lotes as $lote) {
            if ($restante <= 0) {
                break;
            }

            $qtdAtual      = (float) $lote->qtd_atual;
            $debito        = min($qtdAtual, $restante);
            $saldoAnterior = $qtdAtual;
            $saldoPosterior = round($qtdAtual - $debito, 4);

            if ($saldoPosterior < 0) {
                throw new Exception('Quantidade atual do lote não pode ser negativa.', 422);
            }

            $this->_compraItemRepository->update($lote, ['qtd_atual' => $saldoPosterior]);

            $detalhes[] = [
                'id_compra_item'  => (int) $lote->id,
                'debito'          => round($debito, 4),
                'saldo_anterior'  => round($saldoAnterior, 4),
                'saldo_posterior' => $saldoPosterior,
            ];

            $restante = round($restante - $debito, 4);
        }

        if ($restante > 0) {
            throw new Exception(
                'Estoque insuficiente para o item ' . $item->descricao,
                422
            );
        }

        $this->recalcularItem($idItem);

        return $detalhes;
    }

    /**
     * Crédito de estoque nos lotes informados — usado no estorno de finalização de carretéis.
     *
     * @param array<int, array{id_compra_item: int, quantidade: float}> $creditos
     * @return array<int, array{id_compra_item: int, credito: float, saldo_anterior: float, saldo_posterior: float}>
     */
    public function creditarEstoqueNosLotes(int|string $idItem, array $creditos): array
    {
        if (empty($creditos)) {
            return [];
        }

        $item = $this->_itemRepository->findByIdForUpdate($idItem);

        if (!$item) {
            throw new Exception('Item não encontrado', 422);
        }

        if (!$item->controla_estoque) {
            return [];
        }

        $detalhes = [];

        foreach ($creditos as $credito) {
            $idCompraItem = (int) $credito['id_compra_item'];
            $qtdCredito   = round((float) $credito['quantidade'], 4);

            if ($qtdCredito <= 0) {
                continue;
            }

            $lote = $this->_compraItemRepository->findByIdForUpdate($idCompraItem);

            if (!$lote) {
                throw new Exception('Lote não encontrado para estorno de estoque.', 422);
            }

            if ((int) $lote->id_item !== (int) $idItem) {
                throw new Exception('Lote não pertence ao item informado.', 422);
            }

            $saldoAnterior  = (float) $lote->qtd_atual;
            $saldoPosterior = round($saldoAnterior + $qtdCredito, 4);
            $qtdOriginal    = (float) $lote->qtd_original;

            if ($saldoPosterior - $qtdOriginal > 0.0001) {
                throw new Exception(
                    'Estorno excede a quantidade original do lote #' . $lote->id . '.',
                    422
                );
            }

            $this->_compraItemRepository->update($lote, ['qtd_atual' => $saldoPosterior]);

            $detalhes[] = [
                'id_compra_item'  => $idCompraItem,
                'credito'         => $qtdCredito,
                'saldo_anterior'  => round($saldoAnterior, 4),
                'saldo_posterior' => $saldoPosterior,
            ];
        }

        $this->recalcularItem($idItem);

        return $detalhes;
    }
}
