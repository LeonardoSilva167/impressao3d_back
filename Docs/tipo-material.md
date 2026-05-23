# Módulo: Tipo Material

## Descrição

Cadastro de tipos de material utilizados no sistema.

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras          |
|-----------|--------|-------------|-----------------|
| descricao | string | Sim         | máx. 120 chars  |

---

## Arquivos Gerados

| Tipo        | Caminho                                                                  |
|-------------|--------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_22_000001_create_tipos_materiais_table.php` |
| Model       | `app/Models/TipoMaterial.php`                                            |
| Controller  | `app/Http/Controllers/TipoMaterialController.php`                        |
| Service     | `app/Services/TipoMaterial/TipoMaterialService.php`                      |
| Rotas       | `routes/routerFiles/tipoMaterialRouter.php`                              |

---

## Rotas

| Método | Endpoint                          | Controller Method          |
|--------|-----------------------------------|----------------------------|
| GET    | /tipo-material/lookups            | listarLookupsTipoMaterial  |
| GET    | /tipo-material/listar             | listarTipoMaterial         |
| GET    | /tipo-material/listar/{id}        | listarTipoMaterialId       |
| POST   | /tipo-material/cadastrar          | createTipoMaterial         |
| PUT    | /tipo-material/editar             | editTipoMaterial           |
| DELETE | /tipo-material/excluir/{id}       | deleteTipoMaterial         |
| GET    | /tipo-material/tipo-material-list | listarTipoMaterialAsync    |

---

## Observações

- Seed inicial na migration com 4 tipos de material: ABS, PETG, PETG HF e PLA.
- A busca paginada filtra por `descricao` e `palavra_chave` (pesquisa em `descricao`).
