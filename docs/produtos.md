# Módulo: Produtos Base

## Descrição

Cadastro de produtos base (identidade do produto, sem cor). O `codigo_base` é gerado automaticamente a partir da configuração global `proximo_codigo_base`. O SKU base é montado a partir do código base, categoria, modelo e, opcionalmente, linha.

**Variações, cores e filamentos não pertencem ao Produto Base.** Esses conceitos são gerenciados na **Composição do Produto**.

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

Retorna apenas os dados do produto base (descrição, SKU, categoria, modelo, linha).

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

## Cadastro — `POST /produtos/cadastrar`

**Request (exemplo):**

```json
{
  "descricao_produto": "Porta Joias",
  "id_categoria": 1,
  "id_modelo": 2,
  "id_linha": 3
}
```

**Response:**

```json
{
  "produtoBase": {
    "data": {
      "id": 7,
      "descricao_produto": "Porta Joias",
      "codigo_base": "1000",
      "sku_base": "1000-prtjs-mncrc-ephvl",
      "id_categoria": 1,
      "id_modelo": 2,
      "id_linha": 3
    },
    "status": true,
    "message": "Produto base cadastrado com sucesso!"
  }
}
```

Path canônico do `id` (fluxo de produção / redirect): `produtoBase.data.id`.

---

## Observações

- Lookups retornam `proximoCodigoBase` para exibição no front.
- Lookups **não** retornam cores, partes base ou variações.
- Ao excluir produto base, composições vinculadas (e suas variações) também são excluídas (soft delete).
- `codigo_base` não é editável após o cadastro.
