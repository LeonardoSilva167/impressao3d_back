# Módulo: Partes Base de Produto

## Descrição

CRUD auxiliar do módulo Produtos. Cadastro de partes base (componentes físicos do produto) com descrição e código identificador único.

**Submenu:** Partes Base

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras              |
|-----------|--------|-------------|---------------------|
| descricao | string | Sim         | máx. 120 chars      |
| codigo    | string | Sim         | máx. 20 chars, único |

---

## Exemplos

| Descrição | Código |
|-----------|--------|
| Tampa     | tp     |
| Base      | bs     |
| Corpo     | crp    |
| Frente    | frt    |
| Verso     | vrs    |

---

## Arquivos

| Tipo         | Caminho                                                          |
|--------------|------------------------------------------------------------------|
| Migration    | `database/migrations/2026_05_29_000022_create_partes_base_table.php` |
| Model        | `app/Models/ParteBase.php`                                       |
| Repository   | `app/Repositories/ParteBase/ParteBaseRepository.php`             |
| Service      | `app/Services/ParteBase/ParteBaseService.php`                    |
| Requests     | `app/Http/Requests/ParteBase/`                                   |
| Controller   | `app/Http/Controllers/ParteBaseController.php`                   |
| Rotas        | `routes/routerFiles/partesBaseProdutosRouter.php`                |

---

## Rotas

| Método | Endpoint                                          | Controller Method      |
|--------|---------------------------------------------------|------------------------|
| GET    | /partes-base-produtos/lookups                     | listarLookupsParteBase |
| GET    | /partes-base-produtos/listar                      | listarParteBase        |
| GET    | /partes-base-produtos/listar/{id}                 | listarParteBaseId      |
| POST   | /partes-base-produtos/cadastrar                   | createParteBase        |
| PUT    | /partes-base-produtos/editar                       | editParteBase          |
| DELETE | /partes-base-produtos/excluir/{id}                 | deleteParteBase        |
| GET    | /partes-base-produtos/partes-base-produtos-list   | listarParteBaseAsync   |

---

## Observações

- Tabela no banco: `partes_base`.
- Busca paginada filtra por `descricao`, `codigo` e `palavra_chave`.
- Código único validado em FormRequest e índice no banco.
