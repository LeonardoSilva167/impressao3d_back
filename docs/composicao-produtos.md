# Módulo: Composição do Produto

## Descrição

Vincula **Produto Base**, **Variações** e **Projeto de Impressão** para definir o custo real de fabricação. O projeto de impressão possui apenas informações técnicas e estimativas; o custo real é calculado e persistido na composição.

A composição é responsável por:

- vincular Produto Base e Projeto de Impressão
- vincular cada item do projeto a um filamento, por variação
- calcular o custo individual de cada item
- calcular o custo total e o tempo total de cada variação

---

## Tabelas

### `produto_composicoes`

| Campo                | Tipo | Obrigatório | Regras                          |
|----------------------|------|-------------|---------------------------------|
| id                   | int  | —           | PK                              |
| id_produto           | FK   | Sim         | referência `produtos_base`      |
| id_projeto_impressao | FK   | Sim         | referência `projetos_impressao` |
| created_at           | —    | —           | automático                      |
| updated_at           | —    | —           | automático                      |
| deleted_at           | —    | —           | soft delete                     |

### `produto_composicao_variacoes`

| Campo                  | Tipo    | Obrigatório | Regras                         |
|------------------------|---------|-------------|--------------------------------|
| id                     | int     | —           | PK                             |
| id_produto_composicao  | FK      | Sim         | referência `produto_composicoes` |
| id_produto_variacao    | FK      | Sim         | referência `produto_variacoes` |
| custo_total_filamentos | decimal | Sim         | somatório dos itens            |
| tempo_total_impressao  | string  | Sim         | formato `HH:mm`                |
| created_at             | —       | —           | automático                     |
| updated_at             | —       | —           | automático                     |

### `produto_composicao_itens`

| Campo                            | Tipo    | Obrigatório | Regras                                      |
|----------------------------------|---------|-------------|---------------------------------------------|
| id                               | int     | —           | PK                                          |
| id_produto_composicao_variacao   | FK      | Sim         | referência `produto_composicao_variacoes`   |
| id_item_projeto                  | FK      | Sim         | referência `projetos_impressao_parte_itens` |
| id_filamento                     | FK      | Sim         | referência `filamentos`                     |
| peso_total                       | decimal | Sim         | gramas                                      |
| tempo_impressao                  | string  | Sim         | formato `HH:mm`                             |
| preco_medio_grama                | decimal | Sim         | snapshot no momento do save                 |
| custo_item                       | decimal | Sim         | `peso_total × preco_medio_grama`            |
| created_at                       | —       | —           | automático                                  |
| updated_at                       | —       | —           | automático                                  |

---

## Regras de Negócio

- Um **Produto Base** pode possuir apenas **uma composição ativa** (não excluída).
- Ao salvar, é obrigatório informar **todas as variações ATIVAS** do produto.
- Cada variação deve conter **todos os itens** do projeto de impressão selecionado.
- **Custo do item:** `peso_total × preco_medio_grama`.
- **Custo total da variação:** somatório de `custo_item`.
- **Tempo total da variação:** somatório dos tempos `HH:mm` dos itens.
- O `preco_medio_grama` é **congelado no momento da composição** — não é recalculado se o preço do filamento mudar depois.
- Na visualização (`GET /listar/{id}`), retorna os filamentos selecionados com os valores históricos salvos.
- Na exclusão da composição, registros filhos (`variacoes` e `itens`) são removidos fisicamente.

---

## Fórmulas

**Custo do item:**

```
custo_item = peso_total × preco_medio_grama
```

Exemplo: `30 × 0,07 = 2,10`

**Custo total da variação:**

```
custo_total_filamentos = Σ custo_item
```

**Tempo total da variação:**

```
tempo_total_impressao = Σ tempo_impressao (formato HH:mm)
```

---

## Arquivos Gerados

| Tipo        | Caminho                                                                                      |
|-------------|----------------------------------------------------------------------------------------------|
| Migrations  | `database/migrations/2026_05_30_000028_create_produto_composicoes_table.php`                 |
| Migrations  | `database/migrations/2026_05_30_000029_create_produto_composicao_variacoes_table.php`      |
| Migrations  | `database/migrations/2026_05_30_000030_create_produto_composicao_itens_table.php`           |
| Models      | `app/Models/ProdutoComposicao.php`                                                           |
| Models      | `app/Models/ProdutoComposicaoVariacao.php`                                                   |
| Models      | `app/Models/ProdutoComposicaoItem.php`                                                       |
| Repository  | `app/Repositories/ProdutoComposicao/ProdutoComposicaoRepository.php`                         |
| Repository  | `app/Repositories/ProdutoComposicaoVariacao/ProdutoComposicaoVariacaoRepository.php`         |
| Repository  | `app/Repositories/ProdutoComposicaoItem/ProdutoComposicaoItemRepository.php`                 |
| Requests    | `app/Http/Requests/ProdutoComposicao/ProdutoComposicaoCadastrarRequest.php`                  |
| Requests    | `app/Http/Requests/ProdutoComposicao/ProdutoComposicaoEditarRequest.php`                     |
| Controller  | `app/Http/Controllers/ProdutoComposicaoController.php`                                       |
| Service     | `app/Services/ProdutoComposicao/ProdutoComposicaoService.php`                                |
| Service     | `app/Services/ProdutoComposicao/ProdutoComposicaoCalculoService.php`                         |
| Rotas       | `routes/routerFiles/composicaoProdutosRouter.php`                                            |

---

## Rotas

| Método | Endpoint                                      | Controller Method                  |
|--------|-----------------------------------------------|------------------------------------|
| GET    | /composicao-produtos/lookups                  | listarLookupsProdutoComposicao     |
| GET    | /composicao-produtos/carregar-composicao      | carregarProdutoComposicao          |
| GET    | /composicao-produtos/carregar                 | carregarProdutoComposicao (alias)  |
| GET    | /composicao-produtos/listar                   | listarProdutoComposicao            |
| GET    | /composicao-produtos/listar/{id}              | listarProdutoComposicaoId          |
| POST   | /composicao-produtos/cadastrar                | createProdutoComposicao            |
| PUT    | /composicao-produtos/editar                   | editProdutoComposicao              |
| DELETE | /composicao-produtos/excluir/{id}             | deleteProdutoComposicao            |
| GET    | /composicao-produtos/composicao-produtos-list | listarProdutoComposicaoAsync       |

> Prefixo completo: `/api/v1/composicao-produtos/...`

---

## Carregamento — `GET /carregar-composicao`

Query params obrigatórios:

| Param                | Tipo | Descrição              |
|----------------------|------|------------------------|
| id_produto_base      | int  | ID do produto base     |
| id_projeto_impressao | int  | ID do projeto de impressão |

> Alias aceito: `id_produto` no lugar de `id_produto_base`. A rota `/carregar` também aponta para o mesmo endpoint.

**Resposta (200):**

```json
{
  "produto": {
    "id": 1,
    "descricao_produto": "Monocromático Ephvl",
    "sku_base": "1000-prtjs-mncrc-ephvl",
    "variacoes": []
  },
  "projeto": {
    "id": 2,
    "nome_original_projeto": "Projeto Tampa",
    "codigo_projeto": "PRJ-001",
    "partes": [
      {
        "id": 1,
        "nome_parte": "Tampa",
        "itens": [
          {
            "id": 10,
            "nome_parte": "Tampa",
            "nome_item": "Tampa",
            "peso_total": 30,
            "tempo_impressao": "01:30",
            "cor": {
              "id": 5,
              "descricao": "BRANCO",
              "hexadecimal": "#FFFFFF"
            }
          }
        ]
      }
    ]
  },
  "filamentos": [
    {
      "id": 3,
      "resumo": "PETG BRANCO PREMIUM",
      "preco_medio_por_grama": 0.07
    }
  ]
}
```

---

## Cadastro — `POST /cadastrar`

**Payload:**

```json
{
  "id_produto": 1,
  "id_projeto_impressao": 2,
  "variacoes": [
    {
      "id_produto_variacao": 5,
      "itens": [
        {
          "id_item_projeto": 10,
          "id_filamento": 3,
          "peso_total": 30,
          "tempo_impressao": "01:30",
          "preco_medio_grama": 0.07
        }
      ]
    }
  ]
}
```

**Resposta (200):**

```json
{
  "produtoComposicao": {
    "data": {},
    "status": true,
    "message": "Composição do produto cadastrada com sucesso!"
  }
}
```

---

## Edição — `PUT /editar`

Mesmo payload do cadastro, incluindo `"id"` da composição.

---

## Visualização — `GET /listar/{id}`

Retorna composição completa com variações, itens, filamentos selecionados e valores históricos de `preco_medio_grama` e `custo_item`.

---

## Async List — `GET /composicao-produtos-list`

| Param         | Tipo   | Descrição                                              |
|---------------|--------|--------------------------------------------------------|
| palavra_chave | string | Busca em produto, SKU base, nome e código do projeto   |

Retorna até **10** registros quando `palavra_chave` é informada.

**Resposta (200):**

```json
[
  {
    "id": 1,
    "descricao_produto": "Monocromático Ephvl",
    "sku_base": "1000-prtjs-mncrc-ephvl",
    "nome_original_projeto": "Projeto Tampa",
    "codigo_projeto": "PRJ-001"
  }
]
```

---

## Listagem paginada — `GET /listar`

Filtros suportados:

| Param                | Descrição                    |
|----------------------|------------------------------|
| id_produto           | Filtra por produto           |
| id_projeto_impressao | Filtra por projeto           |
| descricao_produto    | Like na descrição do produto |
| sku_base             | Like no SKU base             |
| codigo_projeto       | Like no código do projeto    |
| palavra_chave        | Busca combinada              |
| page                 | Página                       |
| perPage              | Itens por página             |

---

## Erros comuns

| Status | Situação                                              |
|--------|-------------------------------------------------------|
| 404    | Composição, produto ou projeto não encontrado         |
| 422    | Produto já possui composição ativa                    |
| 422    | Variações ativas incompletas no payload               |
| 422    | Itens do projeto incompletos em alguma variação       |
| 422    | Filamento ou item do projeto inválido                 |

---

## Observações

- O endpoint `/lookups` retorna listas de produtos, projetos de impressão e filamentos para os selects do formulário.
- O endpoint `/carregar-composicao` é específico deste módulo e complementa as 7 rotas padrão do CRUD.
- `preco_medio_por_grama` nos lookups de filamento usa a mesma regra do módulo de filamentos (`itens.preco_medio_atual` com fallback para `filamentos.preco_medio_grama`).
