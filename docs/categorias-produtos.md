# Módulo: Categorias de Produto

## Descrição

CRUD auxiliar do módulo Produtos. Cadastro de categorias com descrição e código identificador único.

**Submenu:** Categorias

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras              |
|-----------|--------|-------------|---------------------|
| descricao | string | Sim         | máx. 120 chars      |
| codigo    | string | Sim         | máx. 20 chars, único |

---

## Exemplos

| Descrição    | Código |
|--------------|--------|
| Porta Joias  | prtjs  |
| Quadros      | qdr    |
| Letreiros    | ltr    |

---

## Arquivos

| Tipo         | Caminho                                                                 |
|--------------|-------------------------------------------------------------------------|
| Migration    | `database/migrations/2026_05_29_000019_create_categorias_produtos_table.php` |
| Model        | `app/Models/CategoriaProduto.php`                                       |
| Repository   | `app/Repositories/CategoriaProduto/CategoriaProdutoRepository.php`      |
| Service      | `app/Services/CategoriaProduto/CategoriaProdutoService.php`             |
| Requests     | `app/Http/Requests/CategoriaProduto/`                                   |
| Controller   | `app/Http/Controllers/CategoriaProdutoController.php`                   |
| Rotas        | `routes/routerFiles/categoriasProdutosRouter.php`                       |

---

## Rotas

| Método | Endpoint                                    | Controller Method              |
|--------|---------------------------------------------|--------------------------------|
| GET    | /categorias-produtos/lookups                | listarLookupsCategoriaProduto  |
| GET    | /categorias-produtos/listar                 | listarCategoriaProduto         |
| GET    | /categorias-produtos/listar/{id}            | listarCategoriaProdutoId       |
| POST   | /categorias-produtos/cadastrar              | createCategoriaProduto         |
| PUT    | /categorias-produtos/editar                 | editCategoriaProduto           |
| DELETE | /categorias-produtos/excluir/{id}             | deleteCategoriaProduto         |
| GET    | /categorias-produtos/categorias-produtos-list | listarCategoriaProdutoAsync  |

---

## Observações

- Busca paginada filtra por `descricao`, `codigo` e `palavra_chave`.
- Código único validado em FormRequest e índice no banco.
