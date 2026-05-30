# Módulo: Grade de Produtos

## Menu

**Menu:** Produtos

- Produtos Base
- Composição do Produto
- **Grade de Produtos**

---

## Descrição

Responsável por gerar os **produtos finais vendáveis** a partir dos dados já cadastrados em **Produto Base** e **Composição do Produto**.

A Grade **não solicita Projeto de Impressão**. O projeto já foi utilizado na Composição. Ao selecionar um Produto Base, o sistema localiza automaticamente sua composição cadastrada.

Uma grade possui **várias combinações**. Cada combinação é uma fórmula (partes + quantidades) usada para gerar os produtos finais — não é um produto final em si.

| Responsabilidade | Módulo |
|------------------|--------|
| Partes, itens, variações, filamentos, custos individuais | Composição do Produto |
| Combinações de partes, produtos finais, SKU, nome, totais | Grade de Produtos |

---

## Tabelas

### `grades_produtos`

| Campo           | Tipo    | Obrigatório |
|-----------------|---------|-------------|
| id              | int     | PK          |
| id_produto_base | FK      | Sim         |
| descricao       | string  | Sim         |
| status          | boolean | Sim (default true) |

### `grade_produto_combinacoes`

Fórmulas cadastradas na grade (ex.: Produto Completo, Kit Duplo, Somente Cuba).

| Campo            | Tipo   | Obrigatório |
|------------------|--------|-------------|
| id               | int    | PK          |
| id_grade_produto | FK     | Sim         |
| descricao        | string | Sim         |

### `grade_produto_combinacao_partes`

Partes e quantidades de cada combinação.

| Campo                         | Tipo | Obrigatório |
|-------------------------------|------|-------------|
| id                            | int  | PK          |
| id_grade_produto_combinacao   | FK   | Sim         |
| id_parte_projeto              | FK   | Sim         |
| quantidade                    | int  | Sim (default 1) |

A mesma parte pode aparecer mais de uma vez na combinação (ex.: Cuba × 2).

### `grade_produto_itens`

Produtos finais gerados pela grade.

| Campo            | Tipo    | Obrigatório |
|------------------|---------|-------------|
| id               | int     | PK          |
| id_grade_produto | FK      | Sim         |
| nome_produto     | string  | Sim — montado automaticamente |
| sku              | string  | Sim — montado automaticamente |
| peso_total       | decimal | Sim         |
| tempo_total      | string  | Sim — formato `HH:MM` |
| custo_total      | decimal | Sim         |
| status           | boolean | Sim (default true) |

---

## Fluxo

```
1. Selecionar Produto Base     → carrega composição automaticamente (sem projeto)
2. Visualizar partes/itens     → partes, itens, variações, filamentos e custos
3. Cadastrar combinações       → cada uma com partes e quantidades
4. Preview (opcional)          → POST /preview-produtos
5. Salvar grade                → POST /cadastrar (gerar_produtos: true)
6. Regenerar produtos          → POST /gerar-produtos/{id}
```

---

## Rotas

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET    | /lookups | Produtos base com flag `possui_composicao` |
| GET    | /carregar-dados?id_produto_base= | Carrega composição do produto (sem projeto) |
| GET    | /carregar-composicao?id_produto_base= | Alias de `/carregar-dados` |
| GET    | /listar | Listagem paginada |
| GET    | /listar/{id} | Detalhe com composição, combinações e produtos gerados |
| POST   | /cadastrar | Cria grade + combinações (+ gera produtos se `gerar_produtos: true`) |
| PUT    | /editar | Edita grade (+ regenera se `gerar_produtos: true`) |
| DELETE | /excluir/{id} | Exclui grade, combinações e produtos gerados |
| POST   | /preview-produtos | Preview dos produtos finais sem persistir |
| POST   | /gerar-produtos/{id} | Gera e persiste produtos finais |
| GET    | /grade-produtos-list | Autocomplete async |

> Prefixo: `/api/v1/grades-produtos/...` (alias legado: `/api/v1/grade-produtos/...`)

---

## Carregar Composição — `GET /carregar-dados?id_produto_base=1`

Localiza automaticamente a composição ativa do produto base e retorna partes, itens, variações, filamentos e custos.

**Não solicita `id_projeto_impressao`.**

---

## Cadastro — `POST /cadastrar`

```json
{
  "id_produto_base": 1,
  "descricao": "Grade Principal",
  "status": true,
  "gerar_produtos": true,
  "combinacoes": [
    {
      "descricao": "Produto Completo",
      "partes": [
        { "id_parte_projeto": 2, "quantidade": 1 },
        { "id_parte_projeto": 3, "quantidade": 1 }
      ]
    },
    {
      "descricao": "Kit Duplo",
      "partes": [
        { "id_parte_projeto": 2, "quantidade": 2 },
        { "id_parte_projeto": 3, "quantidade": 1 }
      ]
    },
    {
      "descricao": "Somente Cuba",
      "partes": [
        { "id_parte_projeto": 2, "quantidade": 1 }
      ]
    }
  ]
}
```

---

## Preview — `POST /preview-produtos`

```json
{
  "id_produto_base": 1,
  "combinacoes": [
    {
      "descricao": "Produto Completo",
      "partes": [
        { "id_parte_projeto": 2, "quantidade": 1 },
        { "id_parte_projeto": 3, "quantidade": 1 }
      ]
    }
  ]
}
```

---

## Geração de Produtos

Ao gerar a grade, o sistema percorre **todas as combinações** cadastradas. Para cada combinação:

1. Expande as partes conforme a quantidade (ex.: Cuba × 2 → dois slots de Cuba)
2. Localiza itens, variações e filamentos de cada parte
3. Gera o produto cartesiano de cores dentro de cada slot e entre os slots
4. Persiste os produtos finais em `grade_produto_itens`

### Nome automático

```
{descricao_produto} - {Parte} {Cor(es)} - {Parte} {Cor(es)} - ...
```

Exemplos:

- `Porta Joias - Cuba Rosa - Tampa Branco`
- `Porta Joias - Cuba Rosa - Cuba Lilás - Tampa Branco` (Kit com 2 Cubas)

### SKU automático

```
{sku_base}-{codigo_cor}-{codigo_cor}-...
```

Exemplo: `1000-prtjs-mncrc-ephvl-rsa-brc`

### Cálculos

| Campo        | Regra                                              |
|--------------|----------------------------------------------------|
| peso_total   | Soma do peso de todos os itens da combinação        |
| tempo_total  | Soma dos tempos de impressão de todos os itens     |
| custo_total  | Soma do custo dos itens + custo dos filamentos    |

---

## Validações

- Produto base deve possuir composição cadastrada
- Ao menos uma combinação deve ser cadastrada
- Cada combinação deve possuir ao menos uma parte
- Partes devem pertencer ao projeto da composição
- Todas as variações das partes utilizadas devem estar confirmadas com filamentos

---

## Exemplo de combinações

**Produto base:** Porta Joias

| Combinação       | Partes              |
|------------------|---------------------|
| Produto Completo | Cuba ×1, Tampa ×1   |
| Kit Duplo        | Cuba ×2, Tampa ×1   |
| Somente Cuba     | Cuba ×1             |

Cada combinação gera todos os produtos finais possíveis a partir das variações de cor cadastradas na composição.
