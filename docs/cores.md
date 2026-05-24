# Módulo: Cores

## Descrição

Cadastro de cores utilizadas no sistema, com suporte a código identificador e valor hexadecimal.

---

## Campos

| Campo        | Tipo   | Obrigatório | Regras          |
|--------------|--------|-------------|-----------------|
| descricao    | string | Sim         | máx. 20 chars   |
| codigo       | string | Sim         | —               |
| hexadecimal  | string | Não         | nullable        |

---

## Arquivos Gerados

| Tipo        | Caminho                                              |
|-------------|------------------------------------------------------|
| Migration   | `database/migrations/2026_05_20_224500_create_cores_table.php` |
| Model       | `app/Models/Cor.php`                                 |
| Controller  | `app/Http/Controllers/CorController.php`             |
| Service     | `app/Services/Cor/CorService.php`                    |
| Rotas       | `routes/routerFiles/coresRouter.php`                 |

---

## Rotas

| Método | Endpoint              | Controller Method      |
|--------|-----------------------|------------------------|
| GET    | /cores/lookups        | listarLookupsCor       |
| GET    | /cores/listar         | listarCor              |
| GET    | /cores/listar/{id}    | listarCorId            |
| POST   | /cores/cadastrar      | createCor              |
| PUT    | /cores/editar         | editCor                |
| DELETE | /cores/excluir/{id}   | deleteCor              |
| GET    | /cores/cores-list     | listarCorAsync         |

---

## Observações

- Sem seed inicial na migration (dados variáveis por projeto).
- O campo `hexadecimal` é opcional e pode ser usado para exibição visual da cor no front-end.
- A busca paginada filtra por `descricao` e `palavra_chave` (pesquisa em `descricao` e `codigo`).
