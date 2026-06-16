# Módulo: Análise de Compras

## Descrição

Módulo analítico e gerencial para visualização de indicadores financeiros relacionados às compras realizadas. **Não substitui** o módulo de Compras — este continua responsável por cadastro, edição, visualização e cancelamento.

Os cálculos são executados diretamente via SQL (agrupamentos e subqueries), considerando apenas compras com status `ATIVA`.

---

## Arquivos

| Tipo       | Caminho                                                       |
|------------|---------------------------------------------------------------|
| Controller | `app/Http/Controllers/CompraAnaliseController.php`            |
| Service    | `app/Services/Compra/CompraAnaliseService.php`                |
| Rotas      | `routes/routerFiles/analiseComprasRouter.php`                 |

Registrado no `routes/api.php` com prefixo `analise-compras`.

---

## Rotas

| Método | Endpoint                              | Controller Method              |
|--------|---------------------------------------|--------------------------------|
| GET    | /analise-compras/lookups              | listarLookupsCompraAnalise     |
| GET    | /analise-compras/analise              | listarCompraAnalise            |

---

## Filtros (query params — todos opcionais)

| Parâmetro                  | Tipo        | Descrição                                           |
|----------------------------|-------------|-----------------------------------------------------|
| data_inicio                | date        | Formato `Y-m-d` — início do período (alias)         |
| data_fim                   | date        | Formato `Y-m-d` — fim do período (alias)            |
| data_inicial               | date        | Formato `Y-m-d` — início do período                 |
| data_final                 | date        | Formato `Y-m-d` — fim do período                    |
| id_plataforma_compra       | int/array   | Multiselect — aceita valor único, array ou CSV      |
| ids_plataforma_compra      | int[]       | Multiselect — ex.: `ids_plataforma_compra[]=1&ids_plataforma_compra[]=2` |
| id_categoria_item          | int/array   | Multiselect — categoria do item                     |
| ids_categoria_item         | int[]       | Multiselect — categorias                            |
| id_item                    | int/array   | Multiselect — item específico                       |
| ids_item                   | int[]       | Multiselect — itens                                 |

Os filtros multiselect utilizam cláusula SQL `IN`. Também é aceito formato CSV: `id_plataforma_compra=1,2,3`.

---

## Lookups

Retorna plataformas de compra e categorias de itens para montagem dos filtros da tela.

```
GET /analise-compras/lookups
```

---

## Indicadores Principais

| Campo               | Fórmula                                                                 |
|---------------------|-------------------------------------------------------------------------|
| total_compras       | Soma de `compras_itens.valor_total`                                     |
| total_frete         | Soma de `compras.valor_frete` (uma vez por compra no conjunto filtrado) |
| total_impostos      | Soma de `compras.valor_imposto`                                         |
| total_taxas         | Soma de `compras.valor_taxa`                                            |
| total_descontos     | Soma de `compras.valor_desconto`                                        |
| total_investido     | compras + frete + impostos + taxas − descontos                          |
| valor_estoque_atual | Soma de `itens.estoque_atual × itens.preco_medio_atual`                |

---

## Agrupamentos Retornados

### resumo_por_item

- `id_item`, `nome_item`, `quantidade_comprada`, `valor_total_comprado`, `custo_medio`
- `custo_medio` = `SUM(valor_total) / SUM(qtd_interna)` (mesma regra do estoque)

### resumo_por_categoria

- `categoria`, `valor_total`

### resumo_por_plataforma

- `plataforma`, `valor_total`

### resumo_mensal

- `ano`, `mes`, `valor_total`

### ranking_itens

Top 10 itens por quantidade comprada:

- `id_item`, `item`, `quantidade`, `valor_comprado`

---

## Regras de Negócio

- Compras canceladas (`status = CANCELADA`) **não participam** dos cálculos.
- Registros com soft delete são ignorados.
- Frete, impostos, taxas e descontos são somados **uma vez por compra**, mesmo quando há filtro por item ou categoria.
- `valor_estoque_atual` respeita filtros de item, categoria, plataforma e período (limitando os itens ao escopo das compras filtradas).

---

## Exemplo de Resposta

```
GET /analise-compras/analise?data_inicio=2026-01-01&data_fim=2026-06-30
```

```json
{
  "status": true,
  "possui_dados": true,
  "message": "Análise carregada com sucesso.",
  "data": {
    "indicadores": {
      "total_compras": 0,
      "total_frete": 0,
      "total_impostos": 0,
      "total_taxas": 0,
      "total_descontos": 0,
      "total_investido": 0,
      "valor_estoque_atual": 0
    },
    "totais": {},
    "resumo_por_item": [],
    "resumo_por_categoria": [],
    "resumo_por_plataforma": [],
    "resumo_mensal": [],
    "ranking_itens": []
  },
  "indicadores": {},
  "totais": {},
  "resumo_por_item": [],
  "resumo_por_categoria": [],
  "resumo_por_plataforma": [],
  "resumo_mensal": [],
  "ranking_itens": []
}
```

Quando não houver registros para os filtros informados:

```json
{
  "status": true,
  "possui_dados": false,
  "message": "Nenhum dado encontrado para os filtros informados.",
  "data": { "...": "indicadores zerados e listas vazias" }
}
```

Os campos também são repetidos na raiz da resposta (`indicadores`, `totais`, etc.) para compatibilidade com consumidores que leem diretamente o body.
