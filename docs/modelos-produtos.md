# Módulo: Modelos de Produto

## Descrição

CRUD auxiliar do módulo Produtos. Cadastro de modelos com descrição e código identificador único.

**Submenu:** Modelos

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras              |
|-----------|--------|-------------|---------------------|
| descricao | string | Sim         | máx. 120 chars      |
| codigo    | string | Sim         | máx. 20 chars, único |

---

## Exemplos

| Descrição      | Código |
|----------------|--------|
| Mini Corações  | mncrc  |
| Rosa Decorada  | rsdcr  |
| Casal Love     | cslv   |

---

## Arquivos

| Tipo         | Caminho                                                              |
|--------------|----------------------------------------------------------------------|
| Migration    | `database/migrations/2026_05_29_000020_create_modelos_produtos_table.php` |
| Model        | `app/Models/ModeloProduto.php`                                       |
| Repository   | `app/Repositories/ModeloProduto/ModeloProdutoRepository.php`         |
| Service      | `app/Services/ModeloProduto/ModeloProdutoService.php`                |
| Requests     | `app/Http/Requests/ModeloProduto/`                                   |
| Controller   | `app/Http/Controllers/ModeloProdutoController.php`                   |
| Rotas        | `routes/routerFiles/modelosProdutosRouter.php`                       |

---

## Rotas

| Método | Endpoint                                | Controller Method           |
|--------|-----------------------------------------|-----------------------------|
| GET    | /modelos-produtos/lookups               | listarLookupsModeloProduto  |
| GET    | /modelos-produtos/listar                | listarModeloProduto         |
| GET    | /modelos-produtos/listar/{id}           | listarModeloProdutoId       |
| POST   | /modelos-produtos/cadastrar             | createModeloProduto         |
| PUT    | /modelos-produtos/editar                | editModeloProduto           |
| DELETE | /modelos-produtos/excluir/{id}          | deleteModeloProduto         |
| GET    | /modelos-produtos/modelos-produtos-list | listarModeloProdutoAsync    |

---

## Observações

- Busca paginada filtra por `descricao`, `codigo` e `palavra_chave`.
- Código único validado em FormRequest e índice no banco.
