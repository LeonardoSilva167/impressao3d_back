# Módulo: Composição do Produto

## Descrição

Vincula **Produto Base** e **Projeto de Impressão**. A composição **não gera produtos finais, SKUs finais ou combinações entre itens**. Ela apenas:

1. Cadastra a composição (produto + projeto)
2. Permite configurar cores **por item da parte** (Primária, Secundária, Terciária)
3. Gera **variações individuais** de cada item (ex.: Base Tampa → Rosa)
4. Vincula filamentos e calcula custo individual por variação

A montagem do produto final será implementada em outro módulo.

---

## Fluxo

```
1. Cadastrar composição     → apenas id_produto + id_projeto_impressao
2. Visualizar composição    → partes + quantidade de itens (sem cores)
3. Configurar Parte         → selecionar cores por item
4. Gerar variações          → preview individual por item+cor
5. Confirmar variações      → persistir no banco
6. Salvar filamentos        → preço congelado + custo calculado
```

---

## Tabelas

### `produto_composicoes`

| Campo                | Tipo | Obrigatório |
|----------------------|------|-------------|
| id                   | int  | PK          |
| id_produto           | FK   | Sim         |
| id_projeto_impressao | FK   | Sim         |

### `produto_composicao_cores`

Vínculo: composição → parte → item → tipo de cor → cor.

| Campo           | Tipo   | Obrigatório |
|-----------------|--------|-------------|
| id              | int    | PK          |
| id_composicao   | FK     | Sim         |
| id_parte        | FK     | Sim         |
| id_item_projeto | FK     | Sim         |
| tipo_cor        | string | Sim — `PRIMARIA`, `SECUNDARIA`, `TERCIARIA` |
| id_cor          | FK     | Sim         |

Unique: `(id_composicao, id_item_projeto, tipo_cor, id_cor)`.

### `produto_variacoes`

Uma variação = um item + um tipo de cor + uma cor. **Sem combinações entre itens.**

| Campo              | Tipo   | Obrigatório |
|--------------------|--------|-------------|
| id                 | int    | PK          |
| id_composicao      | FK     | Sim         |
| id_parte           | FK     | Sim         |
| id_item_projeto    | FK     | Sim         |
| tipo_cor           | string | Sim         |
| id_cor             | FK     | Sim         |
| id_composicao_cor  | FK     | Sim         |

### `produto_variacao_filamentos`

| Campo             | Tipo    | Regra                          |
|-------------------|---------|--------------------------------|
| id_variacao       | FK      | 1 filamento por variação       |
| id_filamento      | FK      |                                |
| preco_medio_grama | decimal | snapshot — nunca recalculado   |
| peso_item         | decimal | gramas                         |
| custo_item        | decimal | peso_item × preco_medio_grama  |

---

## Rotas

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST   | /cadastrar | Cria composição (produto + projeto) |
| GET    | /listar/{id} | Visualiza composição com resumo das partes |
| GET    | /configurar-parte/{id}/{idParte} | Alias: `/{id}/parte/{idParte}/configurar` |
| PUT    | /salvar-cores-parte | Salva cores dos itens de uma parte |
| POST   | /gerar-variacoes/{id}?id_parte=&id_item_projeto= | Preview de variações individuais |
| POST   | /confirmar-variacoes | Persiste variações (opcional id_parte, id_item_projeto) |
| PUT    | /salvar-filamentos | Vincula filamentos (opcional id_parte, id_item_projeto) |

> Prefixo: `/api/v1/composicao-produtos/...`

---

## Cadastro — `POST /cadastrar`

**Request:**

```json
{
  "id_produto": 1,
  "id_projeto_impressao": 2
}
```

**Response:**

```json
{
  "produtoComposicao": {
    "data": {
      "id": 6,
      "id_produto": 7,
      "id_projeto_impressao": 12,
      "produto": {
        "descricao_produto": "Porta Joias",
        "sku_base": "1000-prtjs-mncrc",
        "codigo_base": "1000"
      },
      "projeto": {
        "nome_original_projeto": "Porta Joias Mini",
        "codigo_projeto": "PRTJS-001",
        "descricao_projeto": "...",
        "partes": []
      },
      "quantidade_variacoes": 0
    },
    "status": true,
    "message": "Composição do produto cadastrada com sucesso!"
  }
}
```

Path canônico do `id` (fluxo de produção / redirect para view do vínculo): `produtoComposicao.data.id`.

> **Importante para o front:** o `id` **não** fica em `response.id` nem em `response.data.id` no topo. Está dentro do envelope `produtoComposicao`.

---

## Visualização — `GET /listar/{id}`

Retorna o vínculo com `partes_resumo` (também em `projeto.partes`). **Sem campos de cor.**

Cada parte inclui:

| Campo | Descrição |
|-------|-----------|
| `id` / `id_projeto_impressao_parte` | Id da parte do projeto |
| `nome_parte` | Nome |
| `quantidade_itens` | Qtd. de itens da parte |
| `cores_configuradas` | Possui cores em `produto_composicao_cores` |
| `variacoes_geradas` | Possui variações em `produto_variacoes` |
| `quantidade_variacoes` | Total de variações (legado) |
| `total_variacoes` | Alias estável de `quantidade_variacoes` |
| `variacoes_com_filamento` | Variações com `id_filamento` preenchido |
| `configurada` | `cores ∧ variações ∧ total > 0 ∧ filamentos = total` |

Exemplo de item em `partes_resumo`:

```json
{
  "id": 16,
  "id_projeto_impressao_parte": 16,
  "nome_parte": "Base",
  "quantidade_itens": 3,
  "cores_configuradas": true,
  "variacoes_geradas": true,
  "quantidade_variacoes": 4,
  "total_variacoes": 4,
  "variacoes_com_filamento": 2,
  "configurada": false
}
```

---

## Configurar Parte — `GET /configurar-parte/{id}/{idParte}`

Retorna a estrutura **Parte → Itens → Cores configuradas → Variações do item**:

```json
{
  "parte": { "id": 2, "nome_parte": "TAMPA" },
  "itens": [
    {
      "id": 10,
      "nome_item": "Base Tampa",
      "peso_total": 30,
      "tempo_impressao": "01:30:00",
      "cores": {
        "primarias": [{ "id": 1, "id_cor": 3, "tipo_cor": "PRIMARIA", "descricao": "Rosa" }],
        "secundarias": [],
        "terciarias": []
      },
      "variacoes": []
    }
  ],
  "cores_disponiveis": [],
  "tipos_cor": ["PRIMARIA", "SECUNDARIA", "TERCIARIA"],
  "filamentos": []
}
```

---

## Salvar Cores da Parte — `PUT /salvar-cores-parte`

Todos os itens da parte devem ser enviados. Cores vinculadas ao **item**, não à parte.

```json
{
  "id_composicao": 1,
  "id_parte": 2,
  "itens": [
    {
      "id_item_projeto": 10,
      "cores_primarias": [1, 2, 3],
      "cores_secundarias": [],
      "cores_terciarias": []
    },
    {
      "id_item_projeto": 11,
      "cores_primarias": [5],
      "cores_secundarias": [],
      "cores_terciarias": []
    }
  ]
}
```

Gera variações individuais (exemplo correto):

- Base Tampa → Rosa
- Base Tampa → Azul
- Base Tampa → Lilás
- Pegador Tampa → Branco

**Não gera:** Base Rosa + Pegador Branco (combinação entre itens).

---

## Gerar Variações — `POST /gerar-variacoes/{id}?id_parte=2&id_item_projeto=10`

Preview sem salvar. Query params opcionais: `id_parte`, `id_item_projeto`.

Resposta agrupa por item em `itens[].variacoes` e lista plana em `variacoes`.

---

## Confirmar Variações — `POST /confirmar-variacoes`

```json
{
  "id_composicao": 1,
  "id_parte": 2
}
```

`id_parte` opcional. Confirma apenas a parte informada ou toda a composição.

---

## Salvar Filamentos — `PUT /salvar-filamentos`

```json
{
  "id_composicao": 1,
  "id_parte": 2,
  "filamentos": [
    {
      "id_variacao": 5,
      "id_filamento": 3,
      "peso_item": 30,
      "preco_medio_grama": 0.07
    }
  ]
}
```

`preco_medio_grama` é congelado no momento do cadastro.
