# Módulo: Itens

## Descrição

Cadastro de itens genéricos do sistema — base central do estoque, compras e custos. Todo produto, insumo, equipamento, ferramenta ou filamento será um item.

**Menu:** Estoque → Itens

---

## Campos

| Campo              | Tipo    | Obrigatório | Regras                          |
|--------------------|---------|-------------|---------------------------------|
| id_categoria_item  | FK      | Sim         | referência `categorias_itens`   |
| descricao          | string  | Sim         | máx. 255 chars                  |
| codigo             | string  | Sim         | único, máx. 50 chars            |
| unidade_medida     | string  | Sim         | máx. 20 chars                   |
| estoque_atual      | decimal | Sim         | default `0`, cache recalculado a partir dos lotes |
| preco_medio_atual  | decimal | Sim         | default `0`, cache recalculado a partir dos lotes com estoque |
| controla_estoque   | boolean | Sim         |                                 |
| gera_custo         | boolean | Sim         |                                 |
| ativo              | boolean | Sim         |                                 |

---

## Exemplos de Itens

- PETG HF PRETO VOOLT3D
- Envelope 25x35
- Cola Super Bond
- Alicate Universal
- Impressora Bambu Lab A1

---

## Regras de Negócio

- O campo `codigo` deve ser único no sistema.
- A categoria informada em `id_categoria_item` deve existir e estar ativa (não excluída).
- A listagem assíncrona (`/itens-list`) retorna apenas itens ativos.
- `estoque_atual` e `preco_medio_atual` são **campos cache** — recalculados automaticamente a partir dos lotes (`compras_itens`).
- O preço médio considera apenas lotes com `qtd_atual > 0`.

---

## Arquivos Gerados

| Tipo        | Caminho                                                                  |
|-------------|--------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_23_000003_create_itens_table.php`         |
| Model       | `app/Models/Item.php`                                                    |
| Controller  | `app/Http/Controllers/ItemController.php`                                |
| Service     | `app/Services/Item/ItemService.php`                                      |
| Service     | `app/Services/Item/ItemEstoqueRecalculoService.php`                      |
| Rotas       | `routes/routerFiles/itensRouter.php`                                     |

---

## Rotas

| Método | Endpoint                  | Controller Method   |
|--------|---------------------------|---------------------|
| GET    | /itens/lookups            | listarLookupsItem   |
| GET    | /itens/listar             | listarItem          |
| GET    | /itens/listar/{id}        | listarItemId        |
| POST   | /itens/cadastrar          | createItem          |
| PUT    | /itens/editar             | editItem            |
| DELETE | /itens/excluir/{id}       | deleteItem          |
| GET    | /itens/itens-list         | listarItemAsync     |

---

## Observações

- O endpoint `/lookups` retorna a lista de categorias de itens para o select do formulário.
- A busca paginada filtra por `descricao`, `codigo`, `id_categoria_item`, `ativo` e `palavra_chave` (pesquisa em `descricao` e `codigo`).
