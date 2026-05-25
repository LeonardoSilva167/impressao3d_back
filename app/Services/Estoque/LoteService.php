<?php

namespace App\Services\Estoque;

use App\Services\PaginateService;
use Illuminate\Support\Facades\DB;

class LoteService
{
    public function getLotesPaginate(object $atributes): array
    {
        $query = DB::query();

        $query->select(
            'ent.id',
            'ent.id_compra',
            'comp.data_compra',
            'comp.numero_pedido',
            'comp.status as compra_status',
            'ent.id_item',
            'item.descricao as item_descricao',
            'item.codigo as item_codigo',
            'item.unidade_medida as item_unidade_medida',
            'fil.id as id_filamento',
            'fil.codigo as filamento_codigo',
            'fil.resumo as filamento_resumo',
            'ent.qtd_original',
            'ent.qtd_atual',
            'ent.gramatura_filamento',
            'ent.valor_unitario_real',
            'ent.valor_total',
            'ent.created_at',
        );

        $query->selectRaw(
            'CASE WHEN ent.qtd_original > 0 THEN ROUND(((ent.qtd_original - ent.qtd_atual) / ent.qtd_original) * 100, 2) ELSE 0 END as percentual_utilizado'
        );

        $query->selectRaw(
            "CASE WHEN ent.qtd_atual > 0 THEN 'ATIVO' ELSE 'ZERADO' END as status"
        );

        $query->from('compras_itens as ent');
        $query->join('compras as comp', 'comp.id', '=', 'ent.id_compra');
        $query->join('itens as item', 'item.id', '=', 'ent.id_item');
        $query->leftJoin('filamentos as fil', function ($join) {
            $join->on('fil.id_item', '=', 'ent.id_item')
                ->whereNull('fil.deleted_at');
        });
        $query->whereNull('ent.deleted_at');
        $query->whereNull('comp.deleted_at');
        $query->whereNull('item.deleted_at');
        $query->orderByDesc('comp.data_compra');
        $query->orderBy('ent.created_at');
        $query->orderBy('ent.id');

        if (!empty($atributes->id_item)) {
            $query->where('ent.id_item', $atributes->id_item);
        }

        if (!empty($atributes->id_filamento)) {
            $query->where('fil.id', $atributes->id_filamento);
        }

        if (!empty($atributes->data_compra_inicio)) {
            $query->whereDate('comp.data_compra', '>=', $atributes->data_compra_inicio);
        }

        if (!empty($atributes->data_compra_fim)) {
            $query->whereDate('comp.data_compra', '<=', $atributes->data_compra_fim);
        }

        if (isset($atributes->lotes_ativos) && $atributes->lotes_ativos !== '') {
            if (filter_var($atributes->lotes_ativos, FILTER_VALIDATE_BOOLEAN)) {
                $query->where('ent.qtd_atual', '>', 0);
            }
        }

        if (isset($atributes->lotes_zerados) && $atributes->lotes_zerados !== '') {
            if (filter_var($atributes->lotes_zerados, FILTER_VALIDATE_BOOLEAN)) {
                $query->where('ent.qtd_atual', '<=', 0);
            }
        }

        if (!empty($atributes->palavra_chave)) {
            $chave = $atributes->palavra_chave;
            $query->where(function ($q) use ($chave) {
                $q->where('item.descricao', 'like', '%' . $chave . '%')
                    ->orWhere('item.codigo', 'like', '%' . $chave . '%')
                    ->orWhere('fil.resumo', 'like', '%' . $chave . '%')
                    ->orWhere('comp.numero_pedido', 'like', '%' . $chave . '%');
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
            fn ($row) => $this->formatarLote((array) $row),
            $payload['data'] ?? []
        );

        return $payload;
    }

    private function formatarLote(array $row): array
    {
        return [
            'id'                    => $row['id'],
            'item'                  => [
                'id'             => $row['id_item'],
                'descricao'      => $row['item_descricao'],
                'codigo'         => $row['item_codigo'],
                'unidade_medida' => $row['item_unidade_medida'],
            ],
            'compra'                => [
                'id'             => $row['id_compra'],
                'numero_pedido'  => $row['numero_pedido'],
                'status'         => $row['compra_status'] ?? null,
            ],
            'filamento'             => !empty($row['id_filamento']) ? [
                'id'     => $row['id_filamento'],
                'codigo' => $row['filamento_codigo'],
                'resumo' => $row['filamento_resumo'],
            ] : null,
            'data_compra'           => $row['data_compra'],
            'qtd_original'          => $row['qtd_original'],
            'qtd_atual'             => $row['qtd_atual'],
            'gramatura_filamento'   => $row['gramatura_filamento'],
            'percentual_utilizado'  => $row['percentual_utilizado'],
            'valor_unitario_real'   => $row['valor_unitario_real'],
            'valor_total'           => $row['valor_total'],
            'status'                => $row['status'],
            'created_at'            => $row['created_at'],
        ];
    }
}
