# Módulo: Projetos de Impressão

## Menu

**Produção → Projetos de Impressão**

---

## Descrição

Cadastro de projetos de impressão 3D em três níveis: **Projeto** (dados básicos do MakerWorld), **Parte** (agrupamento lógico) e **Item da Parte** (configuração de impressão, cor, pesos e tempo).

Tempo total, peso total, filamentos e custos não ficam mais no projeto — são derivados das partes e itens.

---

## Hierarquia

```
Projeto (projetos_impressao)
  └── Parte (projetos_impressao_partes)
        └── Item da Parte (projetos_impressao_parte_itens)
              └── Cor (cores) — FK obrigatória
```

---

## Projeto

Informações gerais do MakerWorld.

| Campo                 | Tipo   | Obrigatório | Regras    |
|-----------------------|--------|-------------|-----------|
| url_projeto           | text   | Sim         |           |
| nome_original_projeto | string | Sim         |           |
| codigo_projeto        | string | Sim         | **único** |
| descricao_projeto     | text   | Sim         |           |

### Rotas (`/v1/projetos-impressao`)

| Método | Rota                              |
|--------|-----------------------------------|
| GET    | `/lookups`                        |
| GET    | `/listar`                         |
| GET    | `/listar/{id}`                    |
| POST   | `/cadastrar`                      |
| PUT    | `/editar`                         |
| DELETE | `/excluir/{id}`                   |
| GET    | `/projetos-impressao-list`        |

`GET /listar/{id}` retorna o projeto com `partes[]`, cada parte com `itens[]`.

### Cadastro — `POST /cadastrar`

**Request (exemplo):**

```json
{
  "url_projeto": "https://makerworld.com/...",
  "nome_original_projeto": "Porta Joias Mini",
  "codigo_projeto": "PRTJS-001",
  "descricao_projeto": "Projeto MakerWorld"
}
```

**Response:**

```json
{
  "projetoImpressao": {
    "data": {
      "id": 12,
      "url_projeto": "https://makerworld.com/...",
      "nome_original_projeto": "Porta Joias Mini",
      "codigo_projeto": "PRTJS-001",
      "descricao_projeto": "Projeto MakerWorld",
      "partes": []
    },
    "status": true,
    "message": "Projeto de impressão cadastrado com sucesso!"
  }
}
```

Path canônico do `id` (fluxo de produção / redirect): `projetoImpressao.data.id`.

---

## Parte

Agrupamento lógico (ex.: Tampa, Base, Olho).

| Campo                | Tipo   | Obrigatório | Regras                      |
|----------------------|--------|-------------|-----------------------------|
| id_projeto_impressao | FK     | Sim         | referência `projetos_impressao` |
| nome_parte           | string | Sim         |                             |

### Rotas (`/v1/projetos-impressao-partes`)

| Método | Rota                               |
|--------|------------------------------------|
| GET    | `/lookups`                         |
| GET    | `/listar`                          |
| GET    | `/listar/{id}`                     |
| POST   | `/cadastrar`                       |
| PUT    | `/editar`                          |
| DELETE | `/excluir/{id}`                    |
| GET    | `/projetos-impressao-partes-list`  |

`GET /listar/{id}` retorna a parte com `itens[]`.

---

## Item da Parte

Configuração de impressão de cada peça dentro de uma parte.

| Campo                      | Tipo    | Obrigatório | Regras / Default                          |
|----------------------------|---------|-------------|-------------------------------------------|
| id_projeto_impressao_parte | FK      | Sim         | referência `projetos_impressao_partes`    |
| nome_item                  | string  | Sim         |                                           |
| id_cor                     | FK      | Sim         | referência `cores`                        |
| altura_camada              | decimal | Sim         | default `0.20`                            |
| temperatura_bico           | integer | Sim         | default `210`                             |
| temperatura_mesa           | integer | Sim         | default `75`                              |
| loops_parede               | integer | Sim         | default `2`                               |
| tempo_impressao            | string  | Sim         | formato `HH:mm` (ex.: `01:15`)            |
| peso_parte                 | decimal | Sim         | `> 0`                                     |
| peso_suporte               | decimal | Não         | default `0`                               |
| peso_corado                | decimal | Não         | default `0`                               |
| peso_torre                 | decimal | Não         | default `0`                               |
| peso_total                 | virtual | Auto        | `peso_parte + peso_suporte + peso_corado + peso_torre` |
| usa_suporte                | boolean | Sim         | alias: `possui_suporte`                   |
| angulo_suporte             | decimal | Condicional | se `usa_suporte = true`                   |
| tipo_suporte               | enum    | Condicional | `ARVORE_PADRAO`, `ARVORE_FORTE`, `NORMAL` |
| distancia_z_inferior       | decimal | Não         | alias: `distancia_inferior_z`             |
| quantidade_voltas_suporte  | integer | Não         | alias: `quantidade_voltas`                |
| usa_brim                   | boolean | Sim         | alias: `possui_brim`                      |
| usa_engomagem              | boolean | Sim         | alias: `possui_engomar`                   |
| velocidade_engomagem       | decimal | Condicional | alias: `velocidade`                       |
| fluxo_engomagem            | decimal | Condicional | alias: `fluxo`                            |

### Rotas (`/v1/projetos-impressao-parte-itens`)

| Método | Rota                                    |
|--------|-----------------------------------------|
| GET    | `/lookups`                              |
| GET    | `/listar`                               |
| GET    | `/listar/{id}`                          |
| POST   | `/cadastrar`                            |
| PUT    | `/editar`                               |
| DELETE | `/excluir/{id}`                         |
| GET    | `/projetos-impressao-parte-itens-list`   |

Filtros de listagem: `id_projeto_impressao`, `id_projeto_impressao_parte`, `nome_item`, `palavra_chave`.

---

## Exemplo de estrutura

**Projeto:** Cabeça do Robô

**Parte:** Olho

**Itens:**
- Globo Ocular
- Íris
- Pupila

---

## Arquivos

| Camada      | Projeto | Parte | Item |
|-------------|---------|-------|------|
| Model       | `ProjetoImpressao` | `ProjetoImpressaoParte` | `ProjetoImpressaoParteItem` |
| Repository  | `ProjetoImpressao/` | `ProjetoImpressaoParte/` | `ProjetoImpressaoParteItem/` |
| Service     | `ProjetoImpressao/` | `ProjetoImpressaoParte/` | `ProjetoImpressaoParteItem/` |
| Controller  | `ProjetoImpressaoController` | `ProjetoImpressaoParteController` | `ProjetoImpressaoParteItemController` |
| Router      | `projetosImpressaoRouter.php` | `projetosImpressaoPartesRouter.php` | `projetosImpressaoParteItensRouter.php` |

Migration de refatoração: `2026_05_29_000017_refatorar_projetos_impressao_estrutura.php`

---

## Cascata de exclusão

- Excluir **projeto** → exclui partes e itens (soft delete)
- Excluir **parte** → exclui itens da parte
- Excluir **item** → apenas o item
