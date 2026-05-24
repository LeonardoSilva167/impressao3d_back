# Módulo: Itens da Compra

## Descrição

Vincula itens a uma compra — representa cada linha de produto/insumo adquirido em uma compra. Uma compra pode conter itens de diferentes categorias: filamentos, insumos, ferramentas, equipamentos e embalagens.

---

## Campos

| Campo          | Tipo    | Obrigatório | Regras                    |
|----------------|---------|-------------|---------------------------|
| id_compra      | FK      | Sim         | referência `compras`      |
| id_item        | FK      | Sim         | referência `itens`        |
| qtd            | decimal | Sim         | deve ser maior que zero   |
| valor_unitario | decimal | Sim         | não pode ser negativo     |
| valor_total    | decimal | Sim         | deve ser `qtd × valor_unitario` |

---

## Regras de Negócio

- A compra informada em `id_compra` deve existir e não estar excluída.
- O item informado em `id_item` deve existir e não estar excluído.
- O `valor_total` deve ser consistente com `qtd × valor_unitario`.
- A listagem é ordenada por data da compra (mais recente primeiro) e descrição do item.
- Filamentos, insumos, ferramentas, equipamentos e embalagens são diferenciados pela categoria do item (`id_categoria_item`).

---

## Arquivos Gerados

| Tipo        | Caminho                                                                    |
|-------------|----------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_24_000002_create_compras_itens_table.php` |
| Model       | `app/Models/CompraItem.php`                                                |
| Controller  | `app/Http/Controllers/CompraItemController.php`                            |
| Service     | `app/Services/CompraItem/CompraItemService.php`                            |
| Rotas       | `routes/routerFiles/comprasItensRouter.php`                                |

---

## Rotas

| Método | Endpoint                          | Controller Method        |
|--------|-----------------------------------|--------------------------|
| GET    | /compras-itens/lookups            | listarLookupsCompraItem  |
| GET    | /compras-itens/listar             | listarCompraItem         |
| GET    | /compras-itens/listar/{id}        | listarCompraItemId       |
| POST   | /compras-itens/cadastrar          | createCompraItem         |
| PUT    | /compras-itens/editar             | editCompraItem           |
| DELETE | /compras-itens/excluir/{id}       | deleteCompraItem         |
| GET    | /compras-itens/compras-itens-list | listarCompraItemAsync    |

---

## Observações

- O endpoint `/lookups` retorna as listas de compras e itens ativos para os selects do formulário.
- A busca paginada filtra por `id_compra`, `id_item`, `id_categoria_item` e `palavra_chave` (pesquisa em descrição/código do item e número do pedido).
- A listagem assíncrona (`/compras-itens-list`) aceita filtro por `id_compra` e `palavra_chave`.
