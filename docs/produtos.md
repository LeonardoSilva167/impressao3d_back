# Módulo: Produtos Base

## Descrição

Cadastro de produtos base (sem cor embutida). O `codigo_base` é gerado automaticamente a partir da configuração global `proximo_codigo_base`. O SKU base é montado a partir do código base, categoria, modelo e, opcionalmente, linha.

---

## Campos

| Campo             | Tipo   | Obrigatório | Regras                                      |
|-------------------|--------|-------------|---------------------------------------------|
| descricao_produto | string | Sim         | máx. 120 chars                              |
| codigo_base       | string | —           | gerado automaticamente (configurações)      |
| id_categoria      | int    | Sim         | FK `categorias_produtos`                    |
| id_modelo         | int    | Sim         | FK `modelos_produtos`                       |
| id_linha          | int    | Não         | FK `linhas_produtos`                        |
| sku_base          | string | —           | gerado automaticamente, único no banco      |

---

## Código base automático

1. Lê `proximo_codigo_base` em `configuracoes`
2. Utiliza como `codigo_base` do produto
3. Salva o produto
4. Incrementa `proximo_codigo_base` (+1)

Valor inicial: **1000**

> Nunca calcular pelo maior código existente. Sempre usar a tabela `configuracoes`.

---

## Fórmula SKU Base

**Com linha:**

```
{codigo_base}-{codigo_categoria}-{codigo_modelo}-{codigo_linha}
```

Exemplo: `1000-prtjs-mncrc-ephvl`

**Sem linha:**

```
{codigo_base}-{codigo_categoria}-{codigo_modelo}
```

Exemplo: `1000-prtjs-mncrc`

---

## View detalhada (`GET /produtos/listar/{id}`)

Retorna:

- dados do produto base
- `sku_base`
- `quantidade_variacoes` (apenas ATIVAS)
- `variacoes` (ATIVAS e INATIVADAS, exceto soft deleted)

---

## Rotas

| Método | Endpoint                    | Controller Method           |
|--------|-----------------------------|-----------------------------|
| GET    | /produtos/lookups           | listarLookupsProdutoBase    |
| GET    | /produtos/listar            | listarProdutoBase           |
| GET    | /produtos/listar/{id}       | listarProdutoBaseId         |
| POST   | /produtos/cadastrar         | createProdutoBase           |
| PUT    | /produtos/editar            | editProdutoBase             |
| DELETE | /produtos/excluir/{id}      | deleteProdutoBase           |
| GET    | /produtos/produtos-list     | listarProdutoBaseAsync      |

---

## Observações

- Lookups retornam `proximoCodigoBase` para exibição no front.
- Ao editar produto base, se o SKU base mudar, os SKUs das variações vinculadas são recalculados.
- Ao excluir produto base, as variações vinculadas também são excluídas (soft delete).
- `codigo_base` não é editável após o cadastro.
