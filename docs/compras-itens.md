# Módulo: Itens da Compra

## Descrição

Vincula itens a uma compra — representa cada linha de produto/insumo adquirido. Suporta compra por unidade, caixa ou peso, com controle de quantidade interna e cálculo automático de custo unitário real.

---

## Campos

| Campo                 | Tipo    | Obrigatório | Regras                                      |
|-----------------------|---------|-------------|---------------------------------------------|
| id_compra             | FK      | Sim         | referência `compras`                        |
| id_item               | FK      | Sim         | referência `itens`                          |
| qtd_compra            | decimal | Sim         | quantidade comprada (ex: 1 caixa, 2 rolos)  |
| qtd_interna           | decimal | Sim         | quantidade real interna (ex: 1000g, 240 un) |
| valor_unitario_compra | decimal | Sim         | valor pago por unidade comprada             |
| valor_total           | decimal | Sim         | calculado: `qtd_compra × valor_unitario_compra` |
| valor_unitario_real   | decimal | Sim         | calculado: `valor_total / qtd_interna`      |

---

## Significado dos Campos

### qtd_compra
Quantidade comprada na unidade de aquisição.

Exemplos: 1 caixa, 2 filamentos, 3 pacotes.

### qtd_interna
Quantidade real interna total recebida.

Exemplos:
- Filamento: 1000 gramas
- Sacos: 240 unidades
- Parafusos (2 caixas): 1000 unidades

### valor_unitario_compra
Valor pago por unidade comprada.

Exemplo: R$ 89,90 por filamento.

### valor_unitario_real
Custo por unidade interna (grama, unidade, etc.).

Exemplo filamento: R$ 89,90 / 1000g = R$ 0,0899 por grama.

---

## Exemplos Práticos

### Filamento
- qtd_compra: 1
- qtd_interna: 1000 (gramas)
- valor_unitario_compra: 89,90
- valor_total: 89,90
- valor_unitario_real: 0,0899

### Sacos
- qtd_compra: 1
- qtd_interna: 240 (unidades)
- valor_unitario_compra: 24,00
- valor_total: 24,00
- valor_unitario_real: 0,10

### Parafusos
- qtd_compra: 2 (caixas)
- qtd_interna: 1000 (unidades)
- valor_unitario_compra: 40,00
- valor_total: 80,00
- valor_unitario_real: 0,08

---

## Regras de Negócio

- A compra informada em `id_compra` deve existir e não estar excluída.
- O item informado em `id_item` deve existir e não estar excluído.
- `valor_total` e `valor_unitario_real` são calculados automaticamente pelo backend.
- Ao cadastrar/editar/excluir item da compra, o sistema atualiza automaticamente:
  - **estoque** do item (`+qtd_interna` na entrada, reversão na edição/exclusão)
  - **custo médio** do item (média ponderada usando `valor_unitario_real`)
- Respeita flags do item: `controla_estoque` e `gera_custo`.
- A listagem é ordenada por data da compra (mais recente primeiro) e descrição do item.

---

## Arquivos Gerados

| Tipo        | Caminho                                                                    |
|-------------|----------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_24_000002_create_compras_itens_table.php` |
| Migration   | `database/migrations/2026_05_24_000004_alter_compras_itens_restructure.php` |
| Model       | `app/Models/CompraItem.php`                                                |
| Controller  | `app/Http/Controllers/CompraItemController.php`                            |
| Service     | `app/Services/CompraItem/CompraItemService.php`                            |
| Service     | `app/Services/CompraItem/CompraItemEstoqueService.php`                       |
| Repository  | `app/Repositories/CompraItem/CompraItemRepository.php`                     |
| Requests    | `app/Http/Requests/CompraItem/CompraItemCadastrarRequest.php`                |
| Requests    | `app/Http/Requests/CompraItem/CompraItemEditarRequest.php`                   |
| Rotas       | `routes/routerFiles/comprasItensRouter.php`                                |

---

## Rotas

| Método | Endpoint                          | Controller Method        |
|--------|-----------------------------------|--------------------------|
| GET    | /compras-itens/lookups            | listarLookupsCompraItem  |
| GET    | /compras-itens/listar             | listarCompraItem         |
| GET    | /compras-itens/listar/{id}        | listarCompraItemId       |
| POST   | /compras-itens/cadastrar          | createCompraItem         |
| PUT    | /compras-itens/editar             | editCompraItem           |
| DELETE | /compras-itens/excluir/{id}       | deleteCompraItem         |
| GET    | /compras-itens/compras-itens-list | listarCompraItemAsync    |

---

## Payload de Cadastro

```json
{
  "id_compra": 1,
  "id_item": 5,
  "qtd_compra": 1,
  "qtd_interna": 1000,
  "valor_unitario_compra": 89.90
}
```

O backend calcula automaticamente `valor_total` e `valor_unitario_real`.

---

## Observações

- O endpoint `/lookups` retorna as listas de compras e itens ativos (com estoque e custo médio) para os selects do formulário.
- A busca paginada filtra por `id_compra`, `id_item`, `id_categoria_item` e `palavra_chave`.
- A listagem assíncrona (`/compras-itens-list`) aceita filtro por `id_compra` e `palavra_chave`.
