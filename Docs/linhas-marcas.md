# Módulo: Linhas de Marcas

## Descrição

Cadastro de linhas de marcas utilizadas no sistema.

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras          |
|-----------|--------|-------------|-----------------|
| descricao | string | Sim         | máx. 120 chars  |

---

## Arquivos Gerados

| Tipo        | Caminho                                                              |
|-------------|----------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_22_000000_create_linhas_marcas_table.php` |
| Model       | `app/Models/LinhaMarca.php`                                          |
| Controller  | `app/Http/Controllers/LinhaMarcaController.php`                      |
| Service     | `app/Services/LinhaMarca/LinhaMarcaService.php`                      |
| Rotas       | `routes/routerFiles/linhasMarcasRouter.php`                          |

---

## Rotas

| Método | Endpoint                          | Controller Method          |
|--------|-----------------------------------|----------------------------|
| GET    | /linhas-marcas/lookups            | listarLookupsLinhaMarca    |
| GET    | /linhas-marcas/listar             | listarLinhaMarca           |
| GET    | /linhas-marcas/listar/{id}        | listarLinhaMarcaId         |
| POST   | /linhas-marcas/cadastrar          | createLinhaMarca           |
| PUT    | /linhas-marcas/editar             | editLinhaMarca             |
| DELETE | /linhas-marcas/excluir/{id}       | deleteLinhaMarca           |
| GET    | /linhas-marcas/linhas-marcas-list | listarLinhaMarcaAsync      |

---

## Observações

- Seed inicial na migration com 5 linhas de marca padrão.
- A busca paginada filtra por `descricao` e `palavra_chave` (pesquisa em `descricao`).
