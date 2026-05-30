# Módulo: Filamentos

## Descrição

Cadastro especializado de filamentos, vinculado automaticamente a um item genérico do sistema. Cada filamento possui exatamente um item correspondente na categoria `FILAMENTO`.

---

## Campos

| Campo               | Tipo    | Obrigatório | Regras                                      |
|---------------------|---------|-------------|---------------------------------------------|
| id_tipo_material    | FK      | Sim         | referência `tipos_materiais`                |
| id_cor              | FK      | Sim         | referência `cores`                          |
| id_linha_marca      | FK      | Sim         | referência `linhas_marcas`                  |
| id_marca            | FK      | Sim         | referência `marcas`                         |
| id_item             | FK      | Sim         | referência `itens` (único, gerado no backend) |
| codigo              | string  | Sim         | gerado automaticamente (`FIL-000001`)       |
| resumo              | string  | Sim         | gerado automaticamente no backend           |
| qtd                 | decimal | Não         | default `0`, mínimo `0`                     |
| preco_medio_grama   | decimal | Não         | default `0`, mínimo `0`; campo manual no filamento |
| (via item)          | —       | —           | `itens.preco_medio_atual` — preço real por grama (cache de compras/lotes); **prioridade na API** |

---

## Regras de Negócio

- O resumo é montado automaticamente: `tipo_material + cor + linha_marca + marca`.
- O código é gerado automaticamente no padrão `FIL-000001`, `FIL-000002`, etc.
- Não é permitido cadastrar combinação duplicada de `id_tipo_material`, `id_cor`, `id_linha_marca` e `id_marca`.
- Existe índice único composto no banco para garantir a unicidade da combinação.
- Ao cadastrar filamento, o backend cria automaticamente um item vinculado (`id_item`).
- A descrição do item usa o resumo do filamento.
- O código do item usa o mesmo código do filamento.
- A categoria do item é `FILAMENTO`.
- Relação 1:1 entre filamento e item (`id_item` possui constraint UNIQUE).
- Ao editar filamento, a descrição do item vinculado é atualizada com o novo resumo.
- Ao excluir filamento, o item vinculado também é excluído (soft delete).
- O frontend **não** deve enviar `id_item` — o vínculo é gerenciado exclusivamente pelo backend.

---

## Arquivos Gerados

| Tipo        | Caminho                                                                  |
|-------------|--------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_22_000002_create_filamentos_table.php`      |
| Migration   | `database/migrations/2026_05_23_000004_alter_filamentos_add_id_item.php` |
| Model       | `app/Models/Filamento.php`                                               |
| Repository  | `app/Repositories/Filamento/FilamentoRepository.php`                     |
| Requests    | `app/Http/Requests/Filamento/FilamentoCadastrarRequest.php`              |
| Requests    | `app/Http/Requests/Filamento/FilamentoEditarRequest.php`                 |
| Controller  | `app/Http/Controllers/FilamentoController.php`                           |
| Service     | `app/Services/Filamento/FilamentoService.php`                            |
| Rotas       | `routes/routerFiles/filamentosRouter.php`                                |

---

## Rotas

| Método | Endpoint                      | Controller Method        |
|--------|-------------------------------|--------------------------|
| GET    | /filamentos/lookups           | listarLookupsFilamento   |
| GET    | /filamentos/listar            | listarFilamento          |
| GET    | /filamentos/listar/{id}       | listarFilamentoId        |
| POST   | /filamentos/cadastrar         | createFilamento          |
| PUT    | /filamentos/editar            | editFilamento            |
| DELETE | /filamentos/excluir/{id}      | deleteFilamento          |
| GET    | /filamentos/filamentos-list   | listarFilamentoAsync     |

### Consulta individual — `GET /api/v1/filamentos/listar/{id}`

Usado pelo cadastro de Projetos de Impressão para obter resumo, preço por grama e cor do filamento selecionado.

**Resposta (200):**

```json
{
  "id": 25,
  "id_item": 25,
  "resumo": "PETG PRETO PREMIUM VOOLT3D",
  "preco_medio_por_grama": 0.0729,
  "preco_medio_grama": 0.0729,
  "preco_medio_atual": 0.0729,
  "cor": {
    "id": 10,
    "descricao": "PRETO",
    "hexadecimal": "#000000"
  }
}
```

**Erros:**

| Status | Situação                                      |
|--------|-----------------------------------------------|
| 422    | ID inválido (`undefined`, vazio, não numérico)|
| 404    | Filamento não encontrado ou excluído          |

> `preco_medio_por_grama` e `preco_medio_grama` usam `itens.preco_medio_atual` quando o filamento possui `id_item` vinculado; caso contrário, usam `filamentos.preco_medio_grama`. O campo `preco_medio_atual` expõe o valor bruto do item.

---

## Observações

- Os campos `codigo`, `resumo` e `id_item` são somente leitura e não devem ser enviados pelo front-end.
- O endpoint `/lookups` retorna listas de tipos de material, cores, linhas de marcas e marcas para os selects do formulário.
- A busca paginada filtra por `resumo`, `codigo` e `palavra_chave` (pesquisa em `resumo` e `codigo`).
