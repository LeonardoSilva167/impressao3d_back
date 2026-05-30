# Módulo: Linhas de Produto

## Descrição

CRUD auxiliar do módulo Produtos. Cadastro de linhas com descrição e código identificador único.

**Submenu:** Linhas

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras              |
|-----------|--------|-------------|---------------------|
| descricao | string | Sim         | máx. 120 chars      |
| codigo    | string | Sim         | máx. 20 chars, único |

---

## Exemplos

| Descrição   | Código |
|-------------|--------|
| Empilhável  | ephvl  |
| Premium     | prm    |
| Luxo        | lxo    |

---

## Arquivos

| Tipo         | Caminho                                                             |
|--------------|---------------------------------------------------------------------|
| Migration    | `database/migrations/2026_05_29_000021_create_linhas_produtos_table.php` |
| Model        | `app/Models/LinhaProduto.php`                                       |
| Repository   | `app/Repositories/LinhaProduto/LinhaProdutoRepository.php`        |
| Service      | `app/Services/LinhaProduto/LinhaProdutoService.php`                 |
| Requests     | `app/Http/Requests/LinhaProduto/`                                   |
| Controller   | `app/Http/Controllers/LinhaProdutoController.php`                   |
| Rotas        | `routes/routerFiles/linhasProdutosRouter.php`                       |

---

## Rotas

| Método | Endpoint                              | Controller Method          |
|--------|---------------------------------------|----------------------------|
| GET    | /linhas-produtos/lookups              | listarLookupsLinhaProduto  |
| GET    | /linhas-produtos/listar               | listarLinhaProduto         |
| GET    | /linhas-produtos/listar/{id}          | listarLinhaProdutoId       |
| POST   | /linhas-produtos/cadastrar            | createLinhaProduto         |
| PUT    | /linhas-produtos/editar               | editLinhaProduto           |
| DELETE | /linhas-produtos/excluir/{id}         | deleteLinhaProduto         |
| GET    | /linhas-produtos/linhas-produtos-list | listarLinhaProdutoAsync    |

---

## Observações

- Busca paginada filtra por `descricao`, `codigo` e `palavra_chave`.
- Código único validado em FormRequest e índice no banco.
