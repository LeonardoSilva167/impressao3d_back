<?php

namespace App\Services\GradeProduto;

use App\Services\Custo\CustoCalculoService;
use App\Services\ProdutoComposicao\ProdutoComposicaoCalculoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;
use Exception;

class GradeProdutoGeracaoService
{
    private ProdutoComposicaoCalculoService $_calculoService;

    private ProjetoImpressaoParteItemCalculoService $_itemCalculoService;

    private CustoCalculoService $_custoService;

    public function __construct()
    {
        $this->_calculoService     = new ProdutoComposicaoCalculoService();
        $this->_itemCalculoService = new ProjetoImpressaoParteItemCalculoService();
        $this->_custoService       = new CustoCalculoService();
    }

    /**
     * @param  array<int, array<int, object>>  $variacoesPorParte  id_parte => [id_item => [variacoes]]
     * @param  array<int, array{id_parte_projeto: int, quantidade: int}>  $partesCombinacao
     */
    public function gerarProdutos(
        string $descricaoProduto,
        string $skuBase,
        array $variacoesPorParte,
        array $partesCombinacao,
        array $itensProjetoPorId = [],
    ): array {
        $slots = $this->expandirSlotsPartes($partesCombinacao);

        if (empty($slots)) {
            throw new Exception('A combinação deve possuir ao menos uma parte.', 422);
        }

        $combinacoesPorSlot = [];

        foreach ($slots as $idParte) {
            if (empty($variacoesPorParte[$idParte])) {
                throw new Exception('A parte da combinação não possui variações confirmadas.', 422);
            }

            $combinacoesPorSlot[] = $this->combinarVariacoesParte($variacoesPorParte[$idParte], $idParte);
        }

        $combinacoesFinais = $this->produtoCartesianoSlots($combinacoesPorSlot);

        return array_map(
            fn (array $combinacao) => $this->montarProdutoFinal(
                $descricaoProduto,
                $skuBase,
                $combinacao,
                $itensProjetoPorId,
            ),
            $combinacoesFinais,
        );
    }

    /**
     * @param  array<int, array{id_parte_projeto: int, quantidade: int}>  $partesCombinacao
     * @return array<int>
     */
    public function expandirSlotsPartes(array $partesCombinacao): array
    {
        $slots = [];

        foreach ($partesCombinacao as $parte) {
            $parte = (array) $parte;
            $idParte = (int) ($parte['id_parte_projeto'] ?? $parte['id'] ?? 0);
            $quantidade = max(1, (int) ($parte['quantidade'] ?? 1));

            if ($idParte <= 0) {
                continue;
            }

            for ($i = 0; $i < $quantidade; $i++) {
                $slots[] = $idParte;
            }
        }

        return $slots;
    }

    /**
     * @param  array<int, array<object>>  $variacoesPorItem
     */
    private function combinarVariacoesParte(array $variacoesPorItem, int $idParte): array
    {
        $listas = [];

        foreach ($variacoesPorItem as $idItem => $variacoes) {
            if (empty($variacoes)) {
                throw new Exception(
                    'O item da parte não possui variações confirmadas com filamentos.',
                    422
                );
            }

            $listas[] = array_map(
                fn ($variacao) => $this->normalizarVariacao($variacao, $idParte, (int) $idItem),
                $variacoes,
            );
        }

        return $this->produtoCartesiano($listas);
    }

    private function normalizarVariacao(object $variacao, int $idParte, int $idItem): array
    {
        return [
            'id_parte'        => $idParte,
            'id_item_projeto' => $idItem,
            'id_variacao'     => (int) $variacao->id,
            'nome_parte'      => $variacao->nome_parte,
            'nome_item'       => $variacao->nome_item,
            'cor_descricao'   => $variacao->cor_descricao,
            'cor_codigo'      => $variacao->cor_codigo,
            'peso_item'       => isset($variacao->peso_item) ? (float) $variacao->peso_item : null,
            'custo_filamento' => isset($variacao->custo_filamento) ? (float) $variacao->custo_filamento : null,
            'custo_energia'   => isset($variacao->custo_energia) ? (float) $variacao->custo_energia : null,
            'custo_desgaste'  => isset($variacao->custo_desgaste) ? (float) $variacao->custo_desgaste : null,
            'custo_total'     => isset($variacao->custo_total) ? (float) $variacao->custo_total : null,
            'tempo_impressao' => $variacao->tempo_impressao ?? null,
            'peso_parte'      => isset($variacao->peso_parte) ? (float) $variacao->peso_parte : null,
            'peso_suporte'    => isset($variacao->peso_suporte) ? (float) $variacao->peso_suporte : null,
            'peso_corado'     => isset($variacao->peso_corado) ? (float) $variacao->peso_corado : null,
            'peso_torre'      => isset($variacao->peso_torre) ? (float) $variacao->peso_torre : null,
        ];
    }

    /**
     * @param  array<int, array<array>>  $listas
     */
    private function produtoCartesiano(array $listas): array
    {
        $resultado = [[]];

        foreach ($listas as $lista) {
            $temp = [];

            foreach ($resultado as $parcial) {
                foreach ($lista as $item) {
                    $temp[] = array_merge($parcial, [$item]);
                }
            }

            $resultado = $temp;
        }

        return $resultado;
    }

    /**
     * Produto cartesiano entre slots (cada slot = combinação de itens de uma ocorrência da parte).
     *
     * @param  array<int, array<array>>  $combinacoesPorSlot
     */
    private function produtoCartesianoSlots(array $combinacoesPorSlot): array
    {
        $resultado = [[]];

        foreach ($combinacoesPorSlot as $combinacoesSlot) {
            $temp = [];

            foreach ($resultado as $parcial) {
                foreach ($combinacoesSlot as $combinacaoSlot) {
                    $temp[] = array_merge($parcial, [$combinacaoSlot]);
                }
            }

            $resultado = $temp;
        }

        return $resultado;
    }

    /**
     * @param  array<int, array<array>>  $combinacao  cada elemento = variações de um slot
     */
    private function montarProdutoFinal(
        string $descricaoProduto,
        string $skuBase,
        array $combinacao,
        array $itensProjetoPorId,
    ): array {
        $segmentosNome = [];
        $codigosSku    = [];
        $pesos         = [];
        $custos        = [];
        $tempos        = [];
        $variacoesFlat = [];

        foreach ($combinacao as $slotVariacoes) {
            $nomeParte  = $slotVariacoes[0]['nome_parte'] ?? '';
            $coresSlot  = [];
            $idsItensSlot = [];

            foreach ($slotVariacoes as $variacao) {
                $coresSlot[] = $variacao['cor_descricao'];
                $codigosSku[] = $variacao['cor_codigo'];
                $variacoesFlat[] = $variacao;

                $idItem = $variacao['id_item_projeto'];

                if (in_array($idItem, $idsItensSlot, true)) {
                    continue;
                }

                $idsItensSlot[] = $idItem;

                $peso = $this->resolverPesoVariacao($variacao, $itensProjetoPorId[$idItem] ?? null);
                $pesos[] = $peso;
                $custos[] = $this->resolverCustosVariacao($variacao, $itensProjetoPorId[$idItem] ?? null);

                $tempo = $variacao['tempo_impressao']
                    ?? ($itensProjetoPorId[$idItem]->tempo_impressao ?? '00:00');

                $tempos[] = (string) $tempo;
            }

            $segmentosNome[] = trim($nomeParte . ' ' . implode(' ', $coresSlot));
        }

        $nomeProduto = $descricaoProduto . ' - ' . implode(' - ', $segmentosNome);
        $sku         = $skuBase . '-' . implode('-', $codigosSku);
        $custosTotal = $this->_custoService->somarCustos($custos);

        return [
            'nome_produto'    => $nomeProduto,
            'sku'             => $sku,
            'peso_total'      => round(array_sum($pesos), 2),
            'tempo_total'     => $this->_calculoService->somarTempos($tempos),
            'custo_filamento' => $custosTotal['custo_filamento'],
            'custo_energia'   => $custosTotal['custo_energia'],
            'custo_desgaste'  => $custosTotal['custo_desgaste'],
            'custo_total'     => $custosTotal['custo_total'],
            'combinacao'      => $variacoesFlat,
        ];
    }

    private function resolverPesoVariacao(array $variacao, ?object $itemProjeto): float
    {
        if ($variacao['peso_item'] !== null && $variacao['peso_item'] > 0) {
            return round($variacao['peso_item'], 2);
        }

        if ($itemProjeto !== null) {
            return $this->_itemCalculoService->calcularPesoTotal(
                (float) ($itemProjeto->peso_parte ?? 0),
                (float) ($itemProjeto->peso_suporte ?? 0),
                (float) ($itemProjeto->peso_corado ?? 0),
                (float) ($itemProjeto->peso_torre ?? 0),
            );
        }

        if ($variacao['peso_parte'] !== null) {
            return $this->_itemCalculoService->calcularPesoTotal(
                (float) ($variacao['peso_parte'] ?? 0),
                (float) ($variacao['peso_suporte'] ?? 0),
                (float) ($variacao['peso_corado'] ?? 0),
                (float) ($variacao['peso_torre'] ?? 0),
            );
        }

        return 0.0;
    }

    /**
     * @return array{custo_filamento: float, custo_energia: float, custo_desgaste: float, custo_total: float}
     */
    private function resolverCustosVariacao(array $variacao, ?object $itemProjeto): array
    {
        if ($variacao['custo_total'] !== null && $variacao['custo_total'] > 0) {
            return [
                'custo_filamento' => round((float) ($variacao['custo_filamento'] ?? 0), 4),
                'custo_energia'   => round((float) ($variacao['custo_energia'] ?? 0), 4),
                'custo_desgaste'  => round((float) ($variacao['custo_desgaste'] ?? 0), 4),
                'custo_total'     => round((float) $variacao['custo_total'], 4),
            ];
        }

        return [
            'custo_filamento' => 0.0,
            'custo_energia'   => 0.0,
            'custo_desgaste'  => 0.0,
            'custo_total'     => 0.0,
        ];
    }
}
