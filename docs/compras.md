# Módulo: Compras

## Descrição

Cadastro de compras — representa a entrada de itens no negócio. Registra informações da aquisição como plataforma, data, valores e observações.

Compras utilizam **cancelamento lógico** — nunca são excluídas fisicamente.

---

## Campos

| Campo                | Tipo    | Obrigatório | Regras                          |
|----------------------|---------|-------------|---------------------------------|
| id_plataforma_compra | FK      | Sim         | referência `plataforma_compras` |
| data_compra          | date    | Sim         |                                 |
| numero_pedido        | string  | Não         | nullable, máx. 100 chars        |
| valor_frete          | decimal | Sim         | default `0`                     |
| valor_desconto       | decimal | Sim         | default `0`                     |
| valor_taxa           | decimal | Sim         | default `0`                     |
| valor_imposto        | decimal | Sim         | default `0`                     |
| valor_total          | decimal | Sim         |                                 |
| observacao           | text    | Não         | nullable                        |
| status               | enum    | Sim         | `ATIVA` (default) ou `CANCELADA` |

---

## Regras de Negócio

- A plataforma informada em `id_plataforma_compra` deve existir e não estar excluída.
- A listagem é ordenada por `data_compra` (mais recente primeiro).
- Compras **não podem ser excluídas fisicamente** — utilize o cancelamento lógico.
- Compra **ativa** participa normalmente de estoque, lotes e preço médio.
- Compra **cancelada** permanece no banco e na listagem, mas não participa de estoque, preço médio nem lotes ativos.
- Só é possível cancelar se todos os lotes estiverem intactos (`qtd_atual == qtd_original` para cada item).
- Ao cancelar: status vira `CANCELADA`, `qtd_atual` dos lotes é zerada, movimentações `CANCELAMENTO_COMPRA` são geradas e estoque/preço médio são recalculados.
- Compras canceladas não podem ser editadas nem receber novos itens.
- O endpoint `GET /compras/listar/{id}` retorna a compra com itens, lotes e movimentações vinculadas.

---

## Arquivos Gerados

| Tipo        | Caminho                                                                  |
|-------------|--------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_24_000001_create_compras_table.php`       |
| Migration   | `database/migrations/2026_05_24_000003_alter_compras_add_campos_financeiros.php` |
| Migration   | `database/migrations/2026_05_25_000001_alter_compras_add_status.php` |
| Model       | `app/Models/Compra.php`                                                  |
| Controller  | `app/Http/Controllers/CompraController.php`                              |
| Service     | `app/Services/Compra/CompraService.php`                                  |
| Repository  | `app/Repositories/Compra/CompraRepository.php`                           |
| Requests    | `app/Http/Requests/Compra/CompraCadastrarRequest.php`                    |
| Requests    | `app/Http/Requests/Compra/CompraEditarRequest.php`                       |
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
| POST   | /compras/{id}/cancelar      | cancelarCompra      |
| GET    | /compras/compras-list       | listarCompraAsync   |

---

## Filtros de Listagem

| Parâmetro | Valores              | Descrição                          |
|-----------|----------------------|------------------------------------|
| status    | `ATIVA`              | Apenas compras ativas              |
| status    | `CANCELADA`          | Apenas compras canceladas          |
| (vazio)   | —                    | Todas as compras                   |

A listagem retorna `status` e `badge_status` em cada registro.

---

## Observações

- O endpoint `/lookups` retorna a lista de plataformas de compra para o select do formulário.
- A busca paginada filtra por `id_plataforma_compra`, `data_compra`, `numero_pedido`, `status` e `palavra_chave` (pesquisa em `numero_pedido`, `observacao` e descrição da plataforma).
- Os itens da compra são cadastrados separadamente via módulo `/compras-itens`.
- O async `/compras-list` retorna apenas compras **ativas** (para seleção em formulários).
