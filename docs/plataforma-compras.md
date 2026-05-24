# Módulo: Plataforma de Compra

## Descrição

Cadastro de plataformas de compra utilizadas no sistema (marketplaces e lojas online).

---

## Campos

| Campo     | Tipo   | Obrigatório | Regras          |
|-----------|--------|-------------|-----------------|
| descricao | string | Sim         | máx. 120 chars  |
| url       | string | Não         | nullable, máx. 255 chars |

---

## Arquivos Gerados

| Tipo        | Caminho                                                                      |
|-------------|------------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_23_000001_create_plataforma_compras_table.php` |
| Model       | `app/Models/PlataformaCompra.php`                                            |
| Controller  | `app/Http/Controllers/PlataformaCompraController.php`                        |
| Service     | `app/Services/PlataformaCompra/PlataformaCompraService.php`                  |
| Rotas       | `routes/routerFiles/plataformaComprasRouter.php`                             |

---

## Rotas

| Método | Endpoint                                      | Controller Method              |
|--------|-----------------------------------------------|--------------------------------|
| GET    | /plataforma-compras/lookups                   | listarLookupsPlataformaCompra  |
| GET    | /plataforma-compras/listar                    | listarPlataformaCompra         |
| GET    | /plataforma-compras/listar/{id}               | listarPlataformaCompraId       |
| POST   | /plataforma-compras/cadastrar                 | createPlataformaCompra         |
| PUT    | /plataforma-compras/editar                    | editPlataformaCompra           |
| DELETE | /plataforma-compras/excluir/{id}              | deletePlataformaCompra         |
| GET    | /plataforma-compras/plataforma-compras-list   | listarPlataformaCompraAsync    |

---

## Observações

- Seed inicial na migration com 5 plataformas: Shopee, Mercado Livre, Amazon, Voolt3D e GTMax3D.
- A busca paginada filtra por `descricao` e `palavra_chave` (pesquisa em `descricao` e `url`).
