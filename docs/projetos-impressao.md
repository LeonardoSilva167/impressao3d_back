# Módulo: Projetos de Impressão

## Menu

**Produção → Projetos de Impressão**

---

## Descrição

Cadastro de projetos de impressão 3D com informações gerais vindas do MakerWorld, distribuição de peso por cor e configuração individual de impressão por parte.

---

## Hierarquia

```
Projeto (projetos_impressao)
  ├── Cores (projetos_impressao_cores)
  └── Partes (projetos_impressao_partes)
```

### Projeto
Informações gerais do MakerWorld: URL, nome, código, descrição, bico padrão, tempo total e peso total.

### Cores do Projeto
Distribuição do peso total por cor utilizada. A soma dos pesos deve bater com `peso_total_gramas`.

### Parte
Configuração individual de impressão (temperatura, suporte, engomagem, tempo, peso, etc.).

---

## Tabela: projetos_impressao

| Campo                 | Tipo    | Obrigatório | Regras                                      |
|-----------------------|---------|-------------|---------------------------------------------|
| url_projeto           | text    | Sim         |                                             |
| nome_original_projeto | string  | Sim         |                                             |
| codigo_projeto        | string  | Sim         | **único**                                   |
| descricao_projeto     | string  | Sim         |                                             |
| bico_padrao           | enum    | Sim         | `0.2`, `0.4`, `0.6`, `0.8` — default `0.4` |
| tempo_total_horas     | string  | Sim         | formato `HH:mm`; aceita `tempo_total_projeto` decimal/MakerWorld |
| peso_total_gramas     | decimal | Sim         | aceita alias `peso_total_projeto`                                |

---

## Tabela: projetos_impressao_cores

| Campo               | Tipo    | Obrigatório | Regras                          |
|---------------------|---------|-------------|---------------------------------|
| id_projeto_impressao| FK      | Sim         | referência `projetos_impressao` |
| id_cor              | FK      | Sim         | referência `cores`              |
| peso_gramas         | decimal | Sim         |                                 |

---

## Tabela: projetos_impressao_partes

| Campo                      | Tipo    | Obrigatório | Regras                                      |
|----------------------------|---------|-------------|---------------------------------------------|
| id_projeto_impressao       | FK      | Sim         | referência `projetos_impressao`             |
| nome_parte                 | string  | Sim         |                                             |
| altura_camada              | decimal | Sim         | default `0.20`; compatível com bico padrão  |
| temperatura_bico           | integer | Sim         | default `210`                               |
| temperatura_mesa           | integer | Sim         | default `75`                                |
| tempo_impressao            | string  | Sim         | formato `HH:mm`                             |
| peso_parte                 | decimal | Sim         | `DECIMAL(10,2)`; aceita decimais            |
| peso_suporte               | decimal | Não         | default `0`; `DECIMAL(10,2)`                |
| peso_corado                | decimal | Não         | default `0`; `DECIMAL(10,2)`                |
| peso_torre                 | decimal | Não         | default `0`; `DECIMAL(10,2)`                |
| peso_total                 | decimal | Virtual     | `peso_parte + peso_suporte + peso_corado + peso_torre` |
| usa_suporte                | boolean | Sim         |                                             |
| angulo_suporte             | decimal | Condicional | obrigatório se `usa_suporte = true`         |
| tipo_suporte               | enum    | Condicional | `ARVORE_PADRAO`, `ARVORE_FORTE`             |
| distancia_z_inferior       | decimal | Condicional | obrigatório se `usa_suporte = true`         |
| quantidade_voltas_suporte  | integer | Condicional | obrigatório se `usa_suporte = true`         |
| usa_brim                   | boolean | Sim         |                                             |
| usa_engomagem              | boolean | Sim         |                                             |
| velocidade_engomagem       | decimal | Condicional | obrigatório se `usa_engomagem = true`       |
| fluxo_engomagem            | decimal | Condicional | obrigatório se `usa_engomagem = true`       |
| loops_parede               | integer | Sim         | default `2`                                 |

---

## Validação: altura de camada × bico padrão do projeto

| Bico | Alturas permitidas                              |
|------|-------------------------------------------------|
| 0.2  | 0.06, 0.08, 0.10, 0.12, 0.14                  |
| 0.4  | 0.08, 0.12, 0.16, 0.20, 0.24, 0.28            |
| 0.6  | 0.20, 0.24, 0.28, 0.32, 0.36                  |
| 0.8  | 0.28, 0.32, 0.40, 0.48, 0.56                  |

---

## Regras de Negócio

- O `codigo_projeto` deve ser único entre projetos ativos.
- O backend aceita aliases do frontend: `tempo_total_projeto` → `tempo_total_horas` e `peso_total_projeto` → `peso_total_gramas`.
- `tempo_total_horas` é armazenado em formato `HH:mm`. Aceita entrada decimal (`3.5`), MakerWorld (`3.5h`) ou já convertido (`03:30`).
- Conversão decimal → HH:mm: parte inteira = horas; parte decimal × 60 = minutos (ex: `3.5` → `03:30`).
- A soma de `peso_gramas` das cores deve ser igual a `peso_total_gramas`.
- Não é permitido repetir a mesma cor no mesmo projeto.
- `tempo_impressao` das partes usa formato `HH:mm` (ex: `00:30`, `02:15`, `05:40`).
- A altura de camada da parte é validada contra o `bico_padrao` do projeto pai.
- `peso_parte`, `peso_suporte`, `peso_corado` e `peso_torre` aceitam valores decimais (`12.5`, `3.25`, etc.). Strings numéricas são convertidas automaticamente.
- `peso_total` é calculado automaticamente: `peso_parte + peso_suporte + peso_corado + peso_torre`.
- `temperatura_bico` e `temperatura_mesa` possuem defaults `210` e `75`, respectivamente.
- Exclusão em cascata (soft delete): projeto → cores e partes.
- O endpoint `GET /projetos-impressao/listar/{id}` retorna o projeto com cores e partes.

---

## Arquivos Gerados

| Tipo        | Caminho                                                                                  |
|-------------|------------------------------------------------------------------------------------------|
| Migration   | `database/migrations/2026_05_27_000010_create_projetos_impressao_table.php`            |
| Migration   | `database/migrations/2026_05_27_000011_create_projetos_impressao_cores_table.php`      |
| Migration   | `database/migrations/2026_05_27_000012_create_projetos_impressao_partes_table.php`   |
| Migration   | `database/migrations/2026_05_27_000014_alter_projetos_impressao_partes_pesos_temperaturas.php` |
| Models      | `app/Models/ProjetoImpressao.php`, `ProjetoImpressaoCor.php`, `ProjetoImpressaoParte.php` |
| Repositories| `app/Repositories/ProjetoImpressao/`, `ProjetoImpressaoCor/`, `ProjetoImpressaoParte/` |
| Requests    | `app/Http/Requests/ProjetoImpressao/`, `ProjetoImpressaoParte/`                        |
| Controllers | `ProjetoImpressaoController.php`, `ProjetoImpressaoParteController.php`                |
| Services    | `app/Services/ProjetoImpressao/`, `ProjetoImpressaoCor/`, `ProjetoImpressaoParte/`     |
| Rotas       | `routes/routerFiles/projetosImpressaoRouter.php`, `projetosImpressaoPartesRouter.php`  |

---

## Rotas

### Projetos de Impressão — prefixo `/projetos-impressao`

| Método | Endpoint                              | Controller Method              |
|--------|---------------------------------------|--------------------------------|
| GET    | /projetos-impressao/lookups             | listarLookupsProjetoImpressao  |
| GET    | /projetos-impressao/listar              | listarProjetoImpressao         |
| GET    | /projetos-impressao/listar/{id}         | listarProjetoImpressaoId       |
| POST   | /projetos-impressao/cadastrar           | createProjetoImpressao         |
| PUT    | /projetos-impressao/editar              | editProjetoImpressao           |
| DELETE | /projetos-impressao/excluir/{id}        | deleteProjetoImpressao         |
| GET    | /projetos-impressao/projetos-impressao-list | listarProjetoImpressaoAsync |

### Partes — prefixo `/projetos-impressao-partes`

| Método | Endpoint                                            | Controller Method                   |
|--------|-----------------------------------------------------|-------------------------------------|
| GET    | /projetos-impressao-partes/lookups                    | listarLookupsProjetoImpressaoParte  |
| GET    | /projetos-impressao-partes/listar                     | listarProjetoImpressaoParte         |
| GET    | /projetos-impressao-partes/listar/{id}                | listarProjetoImpressaoParteId       |
| POST   | /projetos-impressao-partes/cadastrar                  | createProjetoImpressaoParte         |
| PUT    | /projetos-impressao-partes/editar                     | editProjetoImpressaoParte           |
| DELETE | /projetos-impressao-partes/excluir/{id}               | deleteProjetoImpressaoParte         |
| GET    | /projetos-impressao-partes/projetos-impressao-partes-list | listarProjetoImpressaoParteAsync |

---

## Filtros de Listagem

### Projetos
| Parâmetro             | Descrição                              |
|-----------------------|----------------------------------------|
| nome_original_projeto | Busca parcial no nome                  |
| codigo_projeto        | Busca parcial no código                |
| palavra_chave         | Busca em nome, código e descrição      |

### Partes
| Parâmetro           | Descrição                          |
|---------------------|------------------------------------|
| id_projeto_impressao| Filtra por projeto                 |
| nome_parte          | Busca parcial no nome da parte     |
| palavra_chave       | Busca em nome da parte e projeto   |

---

## Payload de Cadastro — Projeto

```json
{
  "url_projeto": "https://makerworld.com/...",
  "nome_original_projeto": "PORTA ESCOVA STITCH",
  "codigo_projeto": "STITCH",
  "descricao_projeto": "Organizador de escova de dentes",
  "bico_padrao": "0.4",
  "tempo_total_projeto": "3.5",
  "peso_total_projeto": "106",
  "cores": [
    { "id_cor": 1, "peso_gramas": 56 },
    { "id_cor": 2, "peso_gramas": 50 }
  ]
}
```
