# Módulo: Carretéis Finalizados

## Descrição

Registro de finalização de carretéis de filamento. Ao cadastrar, o backend debita estoque automaticamente via FIFO, gera movimentações de estoque e recalcula estoque/preço médio do item vinculado.

**Menu:** Filamentos → Carretéis Finalizados

---

## Campos

| Campo               | Tipo     | Obrigatório | Regras                          |
|---------------------|----------|-------------|---------------------------------|
| id_item             | FK       | Sim*        | referência `itens`              |
| id_filamento        | FK       | Sim*        | alternativa a `id_item`         |
| gramatura           | enum     | Sim         | `500` ou `1000`                 |
| quantidade          | integer  | Sim         | > 0                             |
| qtd_total_consumida | decimal  | Sim         | calculado: `quantidade × gramatura` |
| observacao          | text     | Não         |                                 |
| data_finalizacao    | datetime | Não         | default: data/hora atual        |

\* Informe `id_item` **ou** `id_filamento`. Se enviar `id_filamento`, o backend resolve o `id_item` vinculado.

---

## Regras de Negócio

- O usuário **não** seleciona lote manualmente — consumo sempre via **FIFO** (lote mais antigo com saldo).
- `qtd_total_consumida = quantidade × gramatura` (ex.: 2 carretéis de 1000g = 2000g debitados).
- Não permitir consumo maior que estoque disponível.
- Não permitir gramatura inválida ou quantidade <= 0.
- Gera movimentação automática com tipo `FINALIZACAO_CARRETEL`, registrando item, quantidade, gramatura, lote afetado, saldo anterior e saldo posterior.
- **Edição** com alteração de consumo: estorna o débito anterior nos lotes originais, recalcula estoque/preço médio, aplica novo consumo via FIFO.
- **Exclusão**: estorna todo consumo líquido nos lotes originais e recalcula estoque/preço médio do item.
- Movimentações de estorno usam o tipo `ESTORNO_FINALIZACAO_CARRETEL` para auditoria.

---

## Arquivos Gerados

| Tipo        | Caminho                                                                                      |
|-------------|----------------------------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_24_000008_create_carreteis_finalizados_table.php`               |
| Migration   | `database/migrations/2026_05_24_000009_alter_movimentacoes_estoque_add_finalizacao_carretel.php` |
| Model       | `app/Models/CarreteisFinalizado.php`                                                         |
| Repository  | `app/Repositories/CarreteisFinalizado/CarreteisFinalizadoRepository.php`                     |
| Request     | `app/Http/Requests/CarreteisFinalizado/CarreteisFinalizadoCadastrarRequest.php`              |
| Request     | `app/Http/Requests/CarreteisFinalizado/CarreteisFinalizadoEditarRequest.php`                 |
| Controller  | `app/Http/Controllers/CarreteisFinalizadoController.php`                                     |
| Service     | `app/Services/CarreteisFinalizado/CarreteisFinalizadoService.php`                            |
| Rotas       | `routes/routerFiles/carreteisFinalizadosRouter.php`                                          |

---

## Rotas

| Método | Endpoint                                                      | Controller Method                    |
|--------|---------------------------------------------------------------|--------------------------------------|
| GET    | /carreteis-finalizados/lookups                                | listarLookupsCarreteisFinalizado     |
| GET    | /carreteis-finalizados/listar                                 | listarCarreteisFinalizados           |
| GET    | /carreteis-finalizados/listar/{id}                            | listarCarreteisFinalizadoId          |
| POST   | /carreteis-finalizados/cadastrar                              | createCarreteisFinalizado            |
| PUT    | /carreteis-finalizados/editar                                 | editCarreteisFinalizado              |
| DELETE | /carreteis-finalizados/excluir/{id}                           | deleteCarreteisFinalizado            |
| GET    | /carreteis-finalizados/carreteis-finalizados-list             | listarCarreteisFinalizadoAsync       |
| GET    | /carreteis-finalizados/lote-mais-antigo/{id_item}             | loteMaisAntigo                       |
| GET    | /carreteis-finalizados/lote-mais-antigo-filamento/{id_filamento} | loteMaisAntigoFilamento           |

---

## Edição e exclusão — impacto no estoque

### Editar

Quando `id_item`, `gramatura`, `quantidade` ou `qtd_total_consumida` mudam:

1. Estorna o consumo líquido nos **mesmos lotes** debitados originalmente
2. Gera movimentações `ESTORNO_FINALIZACAO_CARRETEL`
3. Recalcula `estoque_atual` e `preco_medio_atual` do(s) item(ns) afetado(s)
4. Aplica novo consumo via FIFO
5. Gera novas movimentações `FINALIZACAO_CARRETEL`

Se apenas `observacao` ou `data_finalizacao` mudarem, **não há** alteração de estoque.

### Excluir

1. Estorna todo consumo líquido pendente nos lotes originais
2. Gera movimentações `ESTORNO_FINALIZACAO_CARRETEL`
3. Recalcula estoque e preço médio
4. Soft delete do registro em `carreteis_finalizados`

---

## Endpoints

### GET /lookups

Retorna dados para formulário de cadastro:

```json
{
  "gramaturas": [500, 1000],
  "filamentos": [
    {
      "id": 1,
      "id_item": 2,
      "codigo": "FIL-000001",
      "resumo": "PLA Vermelho ...",
      "estoque_atual": "3000.0000",
      "item": {
        "id": 2,
        "descricao": "...",
        "codigo": "FIL-000001"
      }
    }
  ]
}
```

### GET /listar

Listagem paginada. Filtros opcionais:

| Parâmetro                 | Descrição                    |
|---------------------------|------------------------------|
| page, perPage             | Paginação                    |
| id_item                   | Filtrar por item             |
| id_filamento              | Filtrar por filamento        |
| gramatura                 | Filtrar por gramatura        |
| data_finalizacao_inicio   | Data inicial                 |
| data_finalizacao_fim      | Data final                   |
| palavra_chave             | Busca em filamento/item/obs  |

Retorno por registro: filamento, gramatura, quantidade, qtd_total_consumida, data_finalizacao, usuario (null), observacao.

### GET /listar/{id}

Detalhe do registro com movimentações geradas e lotes afetados.

### POST /cadastrar

```json
{
  "id_filamento": 1,
  "gramatura": 1000,
  "quantidade": 2,
  "observacao": "Carretéis esgotados",
  "data_finalizacao": "2026-05-24 14:30:00"
}
```

### GET /carreteis-finalizados-list

Autocomplete de filamentos. Parâmetro `palavra_chave` (limit 10).

### GET /lote-mais-antigo/{id_item}

Consulta auxiliar — retorna lote FIFO mais antigo com saldo (apenas visual):

- compra, numero_pedido, plataforma, data_compra
- qtd_original, qtd_atual, valor_unitario_real

### GET /lote-mais-antigo-filamento/{id_filamento}

Mesma consulta auxiliar, recebendo `id_filamento` em vez de `id_item`.

---

## Observações

- O endpoint `/lookups` retorna filamentos ativos com estoque atual para os selects do formulário.
- O consumo de estoque é automático e irreversível via este módulo.
- Movimentações são vinculadas ao registro via `id_carreteis_finalizados`.
