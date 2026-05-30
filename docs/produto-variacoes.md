# Módulo: Variações de Produto

## Descrição

Variações vinculadas a um produto base, combinando cor primária, secundária e terciária. O sistema gera automaticamente todas as combinações possíveis (produto cartesiano) a partir das listas de cores informadas.

---

## Payload (cadastrar / editar)

```json
{
  "id_produto_base": 1,
  "cores_primarias": [1, 2, 3],
  "cores_secundarias": [4, 5],
  "cores_terciarias": [6, 7]
}
```

| Campo             | Tipo    | Obrigatório | Regras                    |
|-------------------|---------|-------------|---------------------------|
| id_produto_base   | int     | Sim         | FK `produtos_base`        |
| cores_primarias   | int[]   | Sim         | ao menos 1 cor            |
| cores_secundarias | int[]   | Não         | FK `cores`                |
| cores_terciarias  | int[]   | Não         | FK `cores`                |
| sku               | string  | —           | gerado automaticamente    |

Listas secundária/terciária vazias ou omitidas geram combinações com `null` nessa dimensão.

---

## Geração de combinações

Exemplo: 3 primárias × 2 secundárias × 2 terciárias = **12 variações**.

Chave única: `produto + cor_primaria + cor_secundaria + cor_terciaria`

---

## Status

| Valor      | Descrição                                              |
|------------|--------------------------------------------------------|
| ATIVA      | Combinação presente na última sincronização            |
| INATIVADA  | Combinação removida da lista (não é excluída do banco) |

Quando uma combinação deixa de existir na requisição, o registro permanece com `status = INATIVADA`.

---

## Fórmula SKU Variação

```
{sku_base}-{codigo_cor_primaria}[-{codigo_cor_secundaria}][-{codigo_cor_terciaria}]
```

Exemplo: `1000-prtjs-mncrc-ephvl-prt-aml-lls`

---

## Exclusão

`DELETE /produto-variacoes/excluir/{id}` → soft delete (`deleted_at`). Registros excluídos não aparecem nas listagens.

---

## Rotas

| Método | Endpoint                              | Controller Method              |
|--------|---------------------------------------|--------------------------------|
| GET    | /produto-variacoes/lookups            | listarLookupsProdutoVariacao   |
| GET    | /produto-variacoes/listar             | listarProdutoVariacao          |
| GET    | /produto-variacoes/listar/{id}        | listarProdutoVariacaoId        |
| POST   | /produto-variacoes/cadastrar          | createProdutoVariacao (sync)   |
| PUT    | /produto-variacoes/editar             | editProdutoVariacao (sync)     |
| DELETE | /produto-variacoes/excluir/{id}       | deleteProdutoVariacao          |
| GET    | /produto-variacoes/produto-variacoes-list | listarProdutoVariacaoAsync |

---

## Observações

- Cadastrar e editar executam a mesma sincronização de combinações.
- Listagens paginadas e async exibem apenas variações **ATIVAS** e não excluídas.
- Combinações previamente excluídas (soft delete) não são recriadas automaticamente.
