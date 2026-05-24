<?php

namespace App\Services\CompraItem;

use App\Models\CompraItem;
use App\Models\Item;
use App\Repositories\Item\ItemRepository;
use Exception;

class CompraItemEstoqueService
{
    /**
     * @var ItemRepository $_itemRepository
     */
    private ItemRepository $_itemRepository;

    public function __construct()
    {
        $this->_itemRepository = new ItemRepository();
    }

    public function aplicarMovimentacao(CompraItem $compraItem): void
    {
        $item = $this->_itemRepository->findByIdForUpdate($compraItem->id_item);

        if (!$item) {
            throw new Exception('Item não encontrado', 422);
        }

        $this->adicionarEstoqueECusto(
            $item,
            (float) $compraItem->qtd_interna,
            (float) $compraItem->valor_unitario_real
        );
    }

    public function reverterMovimentacao(CompraItem $compraItem): void
    {
        $item = $this->_itemRepository->findByIdForUpdate($compraItem->id_item);

        if (!$item) {
            throw new Exception('Item não encontrado', 422);
        }

        $this->removerEstoque(
            $item,
            (float) $compraItem->qtd_interna
        );
    }

    private function adicionarEstoqueECusto(Item $item, float $qtdInterna, float $valorUnitarioReal): void
    {
        $estoqueAnterior = (float) $item->estoque;
        $custoAnterior   = (float) $item->custo_medio;
        $payload         = [];

        if ($item->controla_estoque) {
            $payload['estoque'] = round($estoqueAnterior + $qtdInterna, 4);
        }

        if ($item->gera_custo) {
            $estoqueParaCalculo = $item->controla_estoque ? $estoqueAnterior : 0;
            $novoEstoque        = $estoqueParaCalculo + $qtdInterna;

            if ($novoEstoque > 0) {
                $payload['custo_medio'] = round(
                    (($estoqueParaCalculo * $custoAnterior) + ($qtdInterna * $valorUnitarioReal)) / $novoEstoque,
                    4
                );
            } else {
                $payload['custo_medio'] = round($valorUnitarioReal, 4);
            }
        }

        if (!empty($payload)) {
            $this->_itemRepository->update($item, $payload);
        }
    }

    private function removerEstoque(Item $item, float $qtdInterna): void
    {
        if (!$item->controla_estoque) {
            return;
        }

        $estoqueAnterior = (float) $item->estoque;
        $novoEstoque     = round($estoqueAnterior - $qtdInterna, 4);

        if ($novoEstoque < 0) {
            throw new Exception(
                'Não é possível reverter a movimentação: estoque insuficiente para o item ' . $item->descricao,
                422
            );
        }

        $payload = ['estoque' => $novoEstoque];

        if ($item->gera_custo && $novoEstoque == 0) {
            $payload['custo_medio'] = 0;
        }

        $this->_itemRepository->update($item, $payload);
    }
}
