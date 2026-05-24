# Módulo: Marcas

## Descrição

Cadastro de marcas utilizadas no sistema.

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras          |
|-----------|--------|-------------|-----------------|
| descricao | string | Sim         | máx. 120 chars  |

---

## Arquivos Gerados

| Tipo        | Caminho                                                        |
|-------------|----------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_20_225000_create_marcas_table.php` |
| Model       | `app/Models/Marca.php`                                         |
| Controller  | `app/Http/Controllers/MarcaController.php`                     |
| Service     | `app/Services/Marca/MarcaService.php`                          |
| Rotas       | `routes/routerFiles/marcasRouter.php`                          |

---

## Rotas

| Método | Endpoint                | Controller Method     |
|--------|-------------------------|-----------------------|
| GET    | /marcas/lookups         | listarLookupsMarca    |
| GET    | /marcas/listar          | listarMarca           |
| GET    | /marcas/listar/{id}     | listarMarcaId         |
| POST   | /marcas/cadastrar       | createMarca           |
| PUT    | /marcas/editar          | editMarca             |
| DELETE | /marcas/excluir/{id}    | deleteMarca           |
| GET    | /marcas/marcas-list     | listarMarcaAsync      |

---

## Observações

- Sem seed inicial na migration.
- A busca paginada filtra por `descricao` e `palavra_chave` (pesquisa em `descricao`).
