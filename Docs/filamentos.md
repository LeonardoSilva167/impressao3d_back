# Módulo: Filamentos

## Descrição

Cadastro de filamentos com composição automática de resumo e código, baseado nos relacionamentos com tipo de material, cor, linha de marca e marca.

---

## Campos

| Campo               | Tipo    | Obrigatório | Regras                                      |
|---------------------|---------|-------------|---------------------------------------------|
| id_tipo_material    | FK      | Sim         | referência `tipos_materiais`                |
| id_cor              | FK      | Sim         | referência `cores`                          |
| id_linha_marca      | FK      | Sim         | referência `linhas_marcas`                  |
| id_marca            | FK      | Sim         | referência `marcas`                         |
| codigo              | string  | Sim         | gerado automaticamente (`FIL-000001`)       |
| resumo              | string  | Sim         | gerado automaticamente no backend           |
| qtd                 | decimal | Não         | default `0`, mínimo `0`                     |
| preco_medio_grama   | decimal | Não         | default `0`, mínimo `0`                     |

---

## Regras de Negócio

- O resumo é montado automaticamente: `tipo_material + cor + linha_marca + marca`.
- O código é gerado automaticamente no padrão `FIL-000001`, `FIL-000002`, etc.
- Não é permitido cadastrar combinação duplicada de `id_tipo_material`, `id_cor`, `id_linha_marca` e `id_marca`.
- Existe índice único composto no banco para garantir a unicidade da combinação.

---

## Arquivos Gerados

| Tipo        | Caminho                                                                  |
|-------------|--------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_22_000002_create_filamentos_table.php`      |
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

---

## Observações

- Os campos `codigo` e `resumo` são somente leitura e não devem ser enviados pelo front-end.
- O endpoint `/lookups` retorna listas de tipos de material, cores, linhas de marcas e marcas para os selects do formulário.
- A busca paginada filtra por `resumo`, `codigo` e `palavra_chave` (pesquisa em `resumo` e `codigo`).
