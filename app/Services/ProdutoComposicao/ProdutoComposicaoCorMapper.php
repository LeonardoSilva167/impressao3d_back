<?php

namespace App\Services\ProdutoComposicao;

use App\Models\ProdutoVariacao;
use Illuminate\Support\Collection;

class ProdutoComposicaoCorMapper
{
    public static function mapCoresPorTipo(Collection $cores): array
    {
        $grupos = [
            'primarias'   => [],
            'secundarias' => [],
            'terciarias'  => [],
        ];

        $chaves = [
            ProdutoVariacao::TIPO_PRIMARIA   => 'primarias',
            ProdutoVariacao::TIPO_SECUNDARIA => 'secundarias',
            ProdutoVariacao::TIPO_TERCIARIA  => 'terciarias',
        ];

        foreach ($cores as $cor) {
            $chave = $chaves[$cor->tipo_cor] ?? null;

            if ($chave === null) {
                continue;
            }

            $grupos[$chave][] = [
                'id'          => (int) $cor->id,
                'id_cor'      => (int) $cor->id_cor,
                'tipo_cor'    => $cor->tipo_cor,
                'descricao'   => $cor->cor_descricao,
                'codigo'      => $cor->cor_codigo,
                'hexadecimal' => $cor->cor_hexadecimal ?? null,
            ];
        }

        return $grupos;
    }

    public static function agruparVariacoesPorItem(array $variacoes): array
    {
        $itens = [];

        foreach ($variacoes as $variacao) {
            $idItem = (int) $variacao['id_item_projeto'];

            if (!isset($itens[$idItem])) {
                $itens[$idItem] = [
                    'id_item_projeto' => $idItem,
                    'nome_item'       => $variacao['nome_item'],
                    'variacoes'       => [],
                ];
            }

            $itens[$idItem]['variacoes'][] = $variacao;
        }

        return array_values($itens);
    }
}
