# Módulo: Compras

## Descrição

Cadastro de compras — representa a entrada de itens no negócio. Registra informações da aquisição como plataforma, data, valores e observações.

---

## Campos

| Campo                | Tipo    | Obrigatório | Regras                          |
|----------------------|---------|-------------|---------------------------------|
| id_plataforma_compra | FK      | Sim         | referência `plataforma_compras` |
| data_compra          | date    | Sim         |                                 |
| numero_pedido        | string  | Não         | nullable, máx. 100 chars        |
| valor_frete          | decimal | Sim         | default `0`                     |
| desconto             | decimal | Sim         | default `0`                     |
| valor_total          | decimal | Sim         |                                 |
| observacao           | text    | Não         | nullable                        |

---

## Regras de Negócio

- A plataforma informada em `id_plataforma_compra` deve existir e não estar excluída.
- A listagem é ordenada por `data_compra` (mais recente primeiro).

---

## Arquivos Gerados

| Tipo        | Caminho                                                                  |
|-------------|--------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_24_000001_create_compras_table.php`       |
| Model       | `app/Models/Compra.php`                                                  |
| Controller  | `app/Http/Controllers/CompraController.php`                              |
| Service     | `app/Services/Compra/CompraService.php`                                  |
| Rotas       | `routes/routerFiles/comprasRouter.php`                                   |

---

## Rotas

| Método | Endpoint                  | Controller Method   |
|--------|---------------------------|---------------------|
| GET    | /compras/lookups            | listarLookupsCompra |
| GET    | /compras/listar             | listarCompra        |
| GET    | /compras/listar/{id}        | listarCompraId      |
| POST   | /compras/cadastrar          | createCompra        |
| PUT    | /compras/editar             | editCompra          |
| DELETE | /compras/excluir/{id}       | deleteCompra        |
| GET    | /compras/compras-list       | listarCompraAsync   |

---

## Observações

- O endpoint `/lookups` retorna a lista de plataformas de compra para o select do formulário.
- A busca paginada filtra por `id_plataforma_compra`, `data_compra`, `numero_pedido` e `palavra_chave` (pesquisa em `numero_pedido`, `observacao` e descrição da plataforma).
