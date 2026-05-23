# Módulo: Categorias de Itens

## Descrição

Cadastro de categorias para classificação de itens do sistema (filamentos, embalagens, insumos, ferramentas, equipamentos, investimentos, etc.).

**Menu:** Estoque → Categorias de Itens

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras          |
|-----------|--------|-------------|-----------------|
| descricao | string | Sim         | máx. 120 chars  |

---

## Arquivos Gerados

| Tipo        | Caminho                                                                      |
|-------------|------------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_23_000002_create_categorias_itens_table.php` |
| Model       | `app/Models/CategoriaItem.php`                                               |
| Controller  | `app/Http/Controllers/CategoriaItemController.php`                           |
| Service     | `app/Services/CategoriaItem/CategoriaItemService.php`                        |
| Rotas       | `routes/routerFiles/categoriasItensRouter.php`                               |

---

## Rotas

| Método | Endpoint                                    | Controller Method              |
|--------|---------------------------------------------|--------------------------------|
| GET    | /categorias-itens/lookups                   | listarLookupsCategoriaItem     |
| GET    | /categorias-itens/listar                    | listarCategoriaItem            |
| GET    | /categorias-itens/listar/{id}               | listarCategoriaItemId          |
| POST   | /categorias-itens/cadastrar                 | createCategoriaItem            |
| PUT    | /categorias-itens/editar                    | editCategoriaItem              |
| DELETE | /categorias-itens/excluir/{id}              | deleteCategoriaItem            |
| GET    | /categorias-itens/categorias-itens-list     | listarCategoriaItemAsync         |

---

## Observações

- Seed inicial na migration com 7 categorias: FILAMENTO, EMBALAGEM, INSUMO_PRODUCAO, INSUMO_GERAL, FERRAMENTA, EQUIPAMENTO e INVESTIMENTO.
- A busca paginada filtra por `descricao` e `palavra_chave` (pesquisa em `descricao`).
