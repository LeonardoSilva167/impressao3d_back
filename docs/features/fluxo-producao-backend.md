# Fluxo de Produção — Especificação Backend

> **Projeto:** `impressao3d_back`  
> **Origem:** [fluxo-producao-ux-orientacao.md](./fluxo-producao-ux-orientacao.md)  
> **Padrão obrigatório:** [crud-template.md](../crud-template.md) e [backend-patterns.md](../backend-patterns.md)  
> **Objetivo:** garantir contratos de API, retornos estáveis de `id` após create, agregados de status das partes e endpoint de progresso — para o front orientar o usuário sem ambiguidade.

---

## 1. Escopo e alinhamento

Esta spec **não cria um CRUD de negócio novo** (produto/projeto/composição/grade já existem). Ela define:

1. **Contratos de create** estáveis (extração de `id` pelo front).
2. **Enriquecimento** do view de composição (`partes_resumo` com status).
3. **Regra de negócio** de “parte configurada” e pré-condição da montagem.
4. **Módulo de leitura** `fluxo-producao` (progresso agregado), seguindo a estrutura Controller → Service → Router do template (sem migration/model próprios).

Módulos impactados (docs existentes):

| Módulo | Doc | Prefixo API |
|--------|-----|-------------|
| Produtos Base | `docs/produtos.md` | `/api/v1/produtos` |
| Projetos de Impressão | `docs/projetos-impressao.md` | `/api/v1/projetos-impressao` |
| Composição do Produto | `docs/composicao-produtos.md` | `/api/v1/composicao-produtos` |
| Grade de Produtos | `docs/grade-produtos.md` | `/api/v1/grades-produtos` (+ alias `grade-produtos`) |
| Fluxo de Produção (novo) | esta spec | `/api/v1/fluxo-producao` |

---

## 2. Estado atual (baseline técnico)

### 2.1 Creates — formato real de resposta hoje

Todos seguem o padrão do template: `handleAdd*` devolve objeto com chave da entidade, e dentro `{ data, status, message }`.

| Endpoint | Envelope atual | Onde está o `id` |
|----------|----------------|------------------|
| `POST /produtos/cadastrar` | `{ produtoBase: { data, status, message } }` | `produtoBase.data.id` |
| `POST /projetos-impressao/cadastrar` | `{ projetoImpressao: { data, status, message } }` | `projetoImpressao.data.id` |
| `POST /composicao-produtos/cadastrar` | `{ produtoComposicao: { data, status, message } }` | `produtoComposicao.data.id` (**já existe**) |
| `POST /grades-produtos/cadastrar` | `{ gradeProduto: { data, status, message } }` | `gradeProduto.data.id` |

> **Risco de integração:** o front pode estar lendo `response.id` ou `response.data.id` no topo. Sem o path do envelope, o redirect cai na listagem. A Fase B0 documenta e valida isso; a Fase B1 padroniza o contrato.

### 2.2 View da composição — `GET /composicao-produtos/listar/{id}`

`getPartesResumoComposicao` já retorna por parte:

| Campo atual | Tipo | Observação |
|-------------|------|------------|
| `id` | int | = `id_projeto_impressao_parte` |
| `nome_parte` | string | |
| `quantidade_itens` | int | |
| `cores_configuradas` | bool | |
| `variacoes_geradas` | bool | |
| `quantidade_variacoes` | int | total de variações da parte |

**Faltam** (pedido da UX Fase 4):

- `configurada` (agregado)
- `total_variacoes` (alias estável para o front; pode espelhar `quantidade_variacoes`)
- `variacoes_com_filamento`

### 2.3 Progresso do fluxo

**Endpoint:** `GET /api/v1/fluxo-producao/progresso?produto={id}`

Resolve `composicao_id` / `projeto_id` / `grade_id` a partir do produto (mais recente), devolve `partes_resumo` (mesma regra B2) e `subpassos` + `proximo_subpasso`.

---

## 3. Subpassos canônicos (contrato compartilhado)

Mesmos códigos da UX:

| Código | Critério de concluído (backend) |
|--------|----------------------------------|
| `E1_PRODUTO` | Existe `produtos_base` com `id = produto_id` e `deleted_at IS NULL` |
| `E2_PROJETO` | Existe `projetos_impressao` referenciado (via query `projeto_id` **ou** via composição do produto) |
| `E2_VINCULO` | Existe `produto_composicoes` com `id_produto = produto_id` e `deleted_at IS NULL` |
| `E2_PARTES` | Todas as partes do projeto vinculado têm `configurada = true` (ver §4) |
| `E3_MONTAGEM` | Existe ao menos uma `grades_produtos` com `id_produto_base = produto_id` e `deleted_at IS NULL` |

### 3.1 Resolução de IDs quando a query está incompleta

Dado apenas `produto_id`:

1. `composicao` ← `produto_composicoes` mais recente (ou única) do produto.
2. `projeto_id` ← `composicao.id_projeto_impressao` (se houver composição).
3. `grade_id` ← grade mais recente do produto (se houver).

Se houver `composicao_id` na query, validar que pertence ao `produto_id`.

---

## 4. Regra de negócio — “parte configurada”

Alinhar com o cálculo do front (`montarPartesResumo`). Uma parte está `configurada = true` **somente se**:

1. Possui ao menos uma cor em `produto_composicao_cores` para a parte; **e**
2. Possui ao menos uma variação em `produto_variacoes` para a parte; **e**
3. `variacoes_com_filamento == total_variacoes` (toda variação tem registro em `produto_variacao_filamentos` com `id_filamento` preenchido).

Equivalente formal:

```text
configurada =
  cores_configuradas
  AND variacoes_geradas
  AND total_variacoes > 0
  AND variacoes_com_filamento = total_variacoes
```

### 4.1 Pré-condição da montagem (Grade)

**Decisão de produto (aceite desta spec):** a montagem (`POST /grades-produtos/cadastrar` e geração de produtos) exige **todas** as partes com `configurada = true`.

- Se alguma parte estiver pendente → `422` com mensagem clara listando partes pendentes.
- Montagem parcial **não** é permitida nesta versão.

> Se no futuro o negócio permitir parcial, basta relaxar `E2_PARTES` para “≥1 parte configurada” e ajustar a validação da grade — o contrato de campos permanece.

---

## 5. Entrega em fases (backend)

Espelha as fases da UX, com numeração `B*` para o time da API.

| Fase UX | Fase Back | Prioridade | Resumo |
|---------|-----------|------------|--------|
| 0 | **B0** | Bloqueante | Auditoria + documentação do contrato de create |
| 1–2, 5 | — | — | Sem mudança de API obrigatória |
| 3 | **B1** | Bloqueante | Garantir `id` estável e documentado no create (especialmente composição) |
| 4 | **B2** | Alta | Enriquecer `partes_resumo` no view da composição |
| 4 / montagem | **B3** | Alta | Validar partes configuradas ao criar/gerar grade |
| 6 | **B4** | Média | Módulo `fluxo-producao` + `GET /progresso` |

Ordem sugerida: **B0 → B1 → B2 → B3 → B4**.

---

### Fase B0 — Auditoria e contrato de create (sem mudança de comportamento)

**Escopo**

- Confirmar (manual ou teste) que os quatro creates do fluxo retornam `data.id` no envelope padrão.
- Documentar o path canônico de leitura para o front (tabela §2.1).
- Atualizar docs dos módulos (`composicao-produtos.md`, `produtos.md`, `projetos-impressao.md`, `grade-produtos.md`) com exemplo de response de create.

**Exemplo canônico — composição**

```json
{
  "produtoComposicao": {
    "data": {
      "id": 6,
      "id_produto": 7,
      "id_projeto_impressao": 12
    },
    "status": true,
    "message": "Composição do produto cadastrada com sucesso!"
  }
}
```

Path para redirect: `produtoComposicao.data.id`.

**Critérios de aceite**

- [x] Docs de create dos 4 módulos listam o envelope e o path do `id`.
- [ ] Front confirma leitura correta (ou aponta gap → Fase B1).

**Auditoria código (2026-07-22)**

| Módulo | Service | `data` no create | `id` presente |
|--------|---------|------------------|---------------|
| Produto | `ProdutoBaseService::createProdutoBase` | model `$newData` | sim (`produtoBase.data.id`) |
| Projeto | `ProjetoImpressaoService::createProjetoImpressao` | `getProjetoImpressaoId` | sim (`projetoImpressao.data.id`) |
| Composição | `ProdutoComposicaoService::createProdutoComposicao` | `getProdutoComposicaoId` | sim (`produtoComposicao.data.id`) |
| Grade | `GradeProdutoService::createGradeProduto` | `getGradeProdutoGradeId` | sim (`gradeProduto.data.id`) |

Conclusão: **nenhum gap de API** para B1 — o contrato já está estável. Pendência só de confirmação do front (path do envelope).

**Arquivos / padrão**

- Apenas documentação em `docs/`. Sem migration.
- Docs atualizados: `produtos.md`, `projetos-impressao.md`, `composicao-produtos.md`, `grade-produtos.md`.

---

### Fase B1 — Contrato estável de create (bloqueante para UX Fase 3)

**Escopo**

Garantir que **todo** create do fluxo devolva o recurso criado com `id` em `*.data`, sem quebrar o envelope do template.

#### B1.1 Regra geral (template)

Manter:

```text
handleAdd{Entidade} → { {entidade}: { data, status, message } }
```

Onde `data` **sempre** inclui `id` (inteiro) do registro criado. Preferência: `data` = retorno completo de `get{Entidade}Id` (como composição e projeto já fazem) ou ao menos objeto com `id`.

#### B1.2 Ajustes pontuais (se auditoria falhar)

| Módulo | Ação se `id` ausente/inacessível |
|--------|----------------------------------|
| Produto | Em `createProdutoBase`, garantir que `data` seja o model/array com `id` (hoje já retorna `$newData`) |
| Projeto | Já retorna `getProjetoImpressaoId` — validar |
| Composição | Já retorna `getProdutoComposicaoId` — validar path no front |
| Grade | Já retorna `getGradeProdutoGradeId` — validar |

#### B1.3 Compatibilidade opcional (somente se o front exigir)

Se o front não puder adaptar o path do envelope no curto prazo, adicionar no **mesmo** response (sem remover o envelope):

```json
{
  "produtoComposicao": { "data": { "id": 6, "...": "..." }, "status": true, "message": "..." },
  "id": 6
}
```

> Preferência da API: **não** poluir o topo. Preferir alinhar o front ao envelope. Só aplicar `id` no topo se for acordado explicitamente.

**Critérios de aceite**

- [x] `POST composicao-produtos/cadastrar` → front consegue `id` e redireciona para view. *(API ok; falta validar no front)*
- [x] Demais creates do fluxo idem. *(API ok)*
- [x] Estrutura `{ data, status, message }` preservada (regra do template).

**Padrão**

- Alterações só em `*Service::create*` / `handleAdd*` existentes — **não necessárias nesta entrega** (auditoria B0 confirmou contrato).
- Sem novo router.

---

### Fase B2 — View da composição: `partes_resumo` enriquecido (UX Fase 4)

**Endpoint:** `GET /api/v1/composicao-produtos/listar/{id}`

**Escopo**

Estender `ProdutoComposicaoService::getPartesResumoComposicao` (e, se útil, espelhar no payload de progresso).

#### B2.1 Campos por parte (contrato)

```json
{
  "id": 16,
  "id_projeto_impressao_parte": 16,
  "nome_parte": "Base",
  "quantidade_itens": 3,
  "cores_configuradas": true,
  "variacoes_geradas": true,
  "quantidade_variacoes": 4,
  "total_variacoes": 4,
  "variacoes_com_filamento": 2,
  "configurada": false
}
```

| Campo | Obrigatório | Origem |
|-------|-------------|--------|
| `id` | Sim | Mantém compatibilidade |
| `id_projeto_impressao_parte` | Sim | Alias explícito de `id` |
| `quantidade_itens` | Sim | Já existe |
| `cores_configuradas` | Sim | Já existe |
| `variacoes_geradas` | Sim | Já existe |
| `quantidade_variacoes` | Sim | Já existe (legado) |
| `total_variacoes` | Sim | = `quantidade_variacoes` |
| `variacoes_com_filamento` | Sim | Contagem nova |
| `configurada` | Sim | Regra §4 |

#### B2.2 Implementação sugerida

- **Service:** `ProdutoComposicaoService::getPartesResumoComposicao`
- **Repository:** em `ProdutoVariacaoRepository` e/ou `ProdutoVariacaoFilamentoRepository`, método:
  - `countComFilamentoByComposicaoId(int $idComposicao, ?int $idParte = null): int`
- Não persistir `configurada` em coluna — sempre calculado.

#### B2.3 (Opcional na mesma fase) bloco `progresso_fluxo` no view

Se o custo for baixo, embutir no mesmo `getProdutoComposicaoId`:

```json
{
  "progresso_fluxo": {
    "produto_id": 7,
    "projeto_id": 12,
    "composicao_id": 6,
    "grade_id": null,
    "subpassos": {
      "E1_PRODUTO": true,
      "E2_PROJETO": true,
      "E2_VINCULO": true,
      "E2_PARTES": false,
      "E3_MONTAGEM": false
    }
  }
}
```

Caso contrário, deixar apenas para **B4**.

**Critérios de aceite**

- [x] View da composição retorna `configurada`, `total_variacoes`, `variacoes_com_filamento` por parte.
- [x] Front consegue badge Configurada/Pendente sem recalcular filamentos no client.
- [x] SoftDeletes respeitados em todas as contagens.

**Implementação:** `ProdutoVariacaoFilamentoRepository::countComFilamentoByComposicaoId` + `ProdutoComposicaoService::getPartesResumoComposicao` (público) + `partes_resumo` no `GET listar/{id}`.

**Docs a atualizar:** `docs/composicao-produtos.md`.

---

### Fase B3 — Validação na montagem (Grade)

**Endpoints**

- `POST /grades-produtos/cadastrar`
- `POST /grades-produtos/gerar-produtos/{id}`
- (e preview, se fizer sentido bloquear preview incompleto — opcional)

**Escopo**

Em `GradeProdutoService`, antes de criar/gerar:

1. Resolver composição do `id_produto_base`.
2. Calcular `partes_resumo` (reutilizar lógica da composição / serviço compartilhado).
3. Se alguma parte `configurada === false` → `422`:

```json
{
  "error": true,
  "message": "Não é possível montar: existem partes sem configuração completa.",
  "partes_pendentes": [
    { "id_projeto_impressao_parte": 16, "nome_parte": "Base" }
  ]
}
```

**Critérios de aceite**

- [x] Produto com parte pendente não gera grade/produtos finais.
- [x] Mensagem lista partes pendentes.
- [x] Regra única compartilhada com §4 (sem duplicar fórmula divergente).

**Implementação:** `GradeProdutoService::validatePartesConfiguradasParaMontagem` reutiliza `ProdutoComposicaoService::getPartesResumoComposicao`. Exceção `PartesPendentesMontagemException` → 422 com `partes_pendentes`. Aplicado em cadastrar, gerar-grade, gerar-produtos, preview e editar.

**Padrão**

- Regra no Service (nunca no Controller).
- Preferir extrair helper/método privado ou service auxiliar `FluxoProducaoProgressoService` (criado em B4) e reutilizar em B3 se B4 já existir; senão método em `ProdutoComposicaoService` chamado pela grade.

---

### Fase B4 — Módulo `fluxo-producao` (UX Fase 6)

Módulo de **leitura** (não é CRUD completo). Ainda assim segue o template naquilo que couber: Controller, Service, Router, registro em `api.php`. **Sem** Migration/Model próprios.

#### B4.1 Checklist de geração (adaptado do template)

- [x] ~~Migration~~ — N/A (agrega dados existentes)
- [x] ~~Model~~ — N/A
- [x] Controller — `app/Http/Controllers/FluxoProducaoController.php`
- [x] Service — `app/Services/FluxoProducao/FluxoProducaoService.php`
- [x] Router — `routes/routerFiles/fluxoProducaoRouter.php`
- [x] Registro em `routes/api.php`
- [x] Doc — este arquivo + referência em docs dos módulos relacionados

#### B4.2 Rotas

**Prefixo:** `/api/v1/fluxo-producao`

Como não há CRUD de entidade, **não** forçar as 7 rotas padrão vazias. Expor apenas o necessário, com o mesmo estilo de controller (try/catch, JSON, status dinâmico):

| Método | Endpoint | Controller | Descrição |
|--------|----------|------------|-----------|
| GET | `/progresso` | `obterProgresso` | Progresso do fluxo por `produto` (obrigatório) |
| GET | `/lookups` | `listarLookupsFluxoProducao` | Opcional — vazio ou metadados de subpassos |

Query params de `/progresso`:

| Param | Obrigatório | Descrição |
|-------|-------------|-----------|
| `produto` | Sim | `produto_id` |
| `projeto` | Não | Se informado, valida consistência |
| `composicao` | Não | Se informado, usa este vínculo |

Exemplo: `GET /api/v1/fluxo-producao/progresso?produto=7`

#### B4.3 Response de progresso

```json
{
  "produto_id": 7,
  "projeto_id": 12,
  "composicao_id": 6,
  "grade_id": 3,
  "produto": {
    "id": 7,
    "descricao_produto": "Porta Joias",
    "sku_base": "1000-prtjs-mncrc",
    "codigo_base": "1000"
  },
  "projeto": {
    "id": 12,
    "nome_original_projeto": "...",
    "codigo_projeto": "..."
  },
  "partes_resumo": [
    {
      "id": 16,
      "id_projeto_impressao_parte": 16,
      "nome_parte": "Base",
      "quantidade_itens": 3,
      "cores_configuradas": true,
      "variacoes_geradas": true,
      "quantidade_variacoes": 4,
      "total_variacoes": 4,
      "variacoes_com_filamento": 4,
      "configurada": true
    }
  ],
  "subpassos": {
    "E1_PRODUTO": true,
    "E2_PROJETO": true,
    "E2_VINCULO": true,
    "E2_PARTES": true,
    "E3_MONTAGEM": true
  },
  "proximo_subpasso": null
}
```

Regras adicionais:

- Se produto não existir → `404`.
- `proximo_subpasso`: primeiro código com `false` na ordem E1 → E2_PROJETO → E2_VINCULO → E2_PARTES → E3_MONTAGEM; `null` se todos `true`.
- Sem composição: `composicao_id`, `projeto_id` (se não veio na query) e `partes_resumo` = `null` / `[]`; `E2_VINCULO` e `E2_PARTES` = `false`.

#### B4.4 Estrutura de arquivos (espelho do template)

**Controller** (`FluxoProducaoController`):

- Construtor com `FluxoProducaoService` + `RequestDataService` (mesmo padrão).
- Métodos com try/catch e `$statusCode` dinâmico.

**Service** (`FluxoProducaoService`):

- `getProgresso(object $params): array` — orquestra resolução de IDs e subpassos.
- Reutiliza `ProdutoComposicaoService` / repositórios existentes para `partes_resumo` (evitar segunda fórmula).
- Sem `handleAdd` / escrita.

**Router** (`fluxoProducaoRouter.php`):

```php
<?php

use App\Http\Controllers\FluxoProducaoController;
use Illuminate\Support\Facades\Route;

Route::get('/progresso', [FluxoProducaoController::class, 'obterProgresso']);
Route::get('/lookups',   [FluxoProducaoController::class, 'listarLookupsFluxoProducao']);
```

**api.php:**

```php
Route::prefix('fluxo-producao')->group(function () {
    require __DIR__ . '/routerFiles/fluxoProducaoRouter.php';
});
```

**Critérios de aceite**

- [x] Hub consegue montar checklist só com `produto_id`, mesmo sem query completa.
- [x] `partes_resumo` idêntico em semântica ao view da composição (B2).
- [x] SoftDeletes respeitados.
- [x] Doc desta feature atualizada com exemplos reais.

---

## 6. Mapa fase UX ↔ entregável backend

```text
UX Fase 0  → B0 (docs + confirmação create)
UX Fase 1  → (nenhum)
UX Fase 2  → B0/B1 (id do projeto)
UX Fase 3  → B1 (id da composição — bloqueante)
UX Fase 4  → B2 (partes_resumo) + B3 (regra montagem)
UX Fase 5  → (GETs existentes bastam; B4 melhora checklist)
UX Fase 6  → B4 (GET /fluxo-producao/progresso)
```

---

## 7. Checklist de alinhamento (API)

- [x] **B0** Creates documentados com path do `id`.
- [x] **B1** `composicao-produtos/cadastrar` sempre permite obter `id` do vínculo criado. *(já ok no código; sem mudança necessária)*
- [x] **B1** Demais creates do fluxo consistentes com o template (`data` + `status` + `message`). *(já ok no código; sem mudança necessária)*
- [x] **B2** View composição: `configurada`, `total_variacoes`, `variacoes_com_filamento` (e alias `id_projeto_impressao_parte`).
- [x] **B3** Grade exige todas as partes configuradas (`422` + `partes_pendentes`).
- [x] **B4** `GET /fluxo-producao/progresso?produto={id}` com subpassos + `partes_resumo`.
- [x] Regra única de “parte configurada” compartilhada (composição + progresso + grade).

---

## 8. Fora de escopo

- Redesign das rotas legadas (`composicao-produtos`, `grade-produtos`).
- Wizard monolítico no backend.
- Persistência de “estado do fluxo” em tabela dedicada (progresso é derivado).
- Alteração de nomenclatura de envelope (`produtoComposicao` etc.) nos creates existentes.

---

## 9. Referência rápida

| Recurso | URL |
|---------|-----|
| Create produto | `POST /api/v1/produtos/cadastrar` |
| Create projeto | `POST /api/v1/projetos-impressao/cadastrar` |
| Create vínculo | `POST /api/v1/composicao-produtos/cadastrar` |
| View vínculo | `GET /api/v1/composicao-produtos/listar/{id}` |
| Create montagem | `POST /api/v1/grades-produtos/cadastrar` |
| Progresso (B4) | `GET /api/v1/fluxo-producao/progresso?produto={id}` |

---

## 10. Referências

- UX: [fluxo-producao-ux-orientacao.md](./fluxo-producao-ux-orientacao.md)
- Template CRUD: [crud-template.md](../crud-template.md)
- Padrões: [backend-patterns.md](../backend-patterns.md)
- Módulos: [produtos.md](../produtos.md), [projetos-impressao.md](../projetos-impressao.md), [composicao-produtos.md](../composicao-produtos.md), [grade-produtos.md](../grade-produtos.md)
