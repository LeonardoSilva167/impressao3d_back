# Fluxo de Produção — Orientação ao Usuário e Continuidade de Contexto

> **Projeto:** `impressao3d_front` (frontend)  
> **Alinhamento:** esta especificação deve ser analisada também no backend para garantir contratos de API, retornos de `id` após cadastro e eventual endpoint de progresso do fluxo.  
> **Objetivo:** reduzir ambiguidade no fluxo guiado — o usuário precisa saber **onde está**, **o que já fez** e **qual é o próximo clique**.

---

## 1. Contexto e problema atual

O hub `/fluxo-producao` e o stepper superior já existem, mas:

- Os botões de ação não deixam claro **por onde começar**.
- O progresso concluído (produto, projeto, vínculo, partes) não é apresentado de forma visual consistente.
- O contexto do fluxo (`produto`, `projeto`, `composicao`) **não é propagado** de ponta a ponta.
- Em algumas telas o CTA de “próximo passo” fica só no topo; no fim da página o usuário não sabe o que fazer.
- Após salvar o vínculo, o usuário pode cair na **listagem** em vez da **view do vínculo criado** (depende do retorno de `id` no create).
- Na view do vínculo, a tabela de partes **não sinaliza** claramente o que já foi configurado nem quantas variações existem.

### Rotas envolvidas (estado atual)

| Etapa | Rota | Papel |
|------:|------|-------|
| Hub | `/fluxo-producao?etapa={1\|2\|3}&produto={id}` | Orientação e entrada das ações |
| 1 | `/produtos/add` → redirect `/fluxo-producao?produto={id}&etapa=2` | Cadastro produto base |
| 2a | `/projetos-impressao/add` → `/projetos-impressao/view/{id}` | Cadastro projeto + partes/itens |
| 2b | `/composicao-produtos/add` | Criar vínculo produto–projeto |
| 2c | `/composicao-produtos/view/{id}` | Configurar partes |
| 2d | `/composicao-produtos/{id}/parte/{idParte}/configurar` | Configuração da parte |
| 3 | `/fluxo-producao?etapa=3` → `/grade-produtos/add` | Montagem |

Labels de domínio (UI): ver `src/constants/dominioProducaoLabels.ts`.

---

## 2. Princípios de UX (válidos para todas as fases)

1. **Um próximo passo óbvio** por tela (CTA primário destacado + texto “Próximo passo: …”).
2. **Checklist / stepper vertical** mostrando o que já foi feito e o que falta.
3. **Contexto persistente** via query string (e, quando útil, `sessionStorage` como fallback):
   - `produto` — id do produto base do fluxo
   - `projeto` — id do projeto de impressão do fluxo
   - `composicao` — id do vínculo criado
   - `fluxo=1` — flag opcional indicando “estou no fluxo guiado”
4. **CTAs duplicados**: ações de continuidade no **topo e no rodapé** da página quando a tela for longa (view de projeto, view de vínculo).
5. **Não quebrar** o uso avulso dos CRUDs (fora do fluxo): pré-preenchimento e banners só quando houver contexto de fluxo.

---

## 3. Modelo de progresso do fluxo (contrato compartilhado Front ↔ Back)

### 3.1 Subpassos canônicos

| Código | Etapa macro | Subpasso | Critério de “concluído” |
|--------|-------------|----------|-------------------------|
| `E1_PRODUTO` | 1 | Cadastrar produto base | Existe `produto.id` |
| `E2_PROJETO` | 2 | Cadastrar projeto (ou selecionar existente) | Existe `projeto.id` |
| `E2_VINCULO` | 2 | Criar vínculo produto–projeto | Existe `composicao.id` |
| `E2_PARTES` | 2 | Configurar partes | Todas as partes com `configurada = true` (ou ≥1 se regra de negócio permitir montagem parcial — **definir com backend**) |
| `E3_MONTAGEM` | 3 | Criar montagem | Existe montagem (`grade`) vinculada ao produto |

### 3.2 Regra de “parte configurada” (já existe no front)

Hoje o front calcula em `montarPartesResumo`:

- cores definidas nos itens da parte; **e**
- variações geradas; **e**
- todas as variações com filamento.

**Pedido ao backend:** preferencialmente expor no `GET composicao-produtos/view/{id}` (ou endpoint de progresso) os campos agregados por parte, para não depender só do cálculo client-side:

```json
{
  "partes_resumo": [
    {
      "id_projeto_impressao_parte": 16,
      "nome_parte": "Base",
      "configurada": false,
      "quantidade_itens": 3,
      "total_variacoes": 0,
      "variacoes_com_filamento": 0
    }
  ],
  "progresso_fluxo": {
    "produto_id": 7,
    "projeto_id": 12,
    "composicao_id": 6,
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

> Se o backend não puder entregar `progresso_fluxo` na Fase 1, o front monta o checklist com os ids presentes na query + dados já carregados nas views. O endpoint agregado fica como **Fase backend / Fase 4**.

### 3.3 Retornos de create (obrigatório para redirects corretos)

| Endpoint (legado) | O que o front precisa no body |
|-------------------|-------------------------------|
| `produtos/cadastrar` | `id` do produto |
| `projetos-impressao/cadastrar` | `id` do projeto |
| `composicao-produtos/cadastrar` | `id` do vínculo (**crítico**: sem isso o redirect cai na listagem) |
| `grade-produtos/cadastrar` | `id` da montagem |

**Aceite backend:** create sempre retorna o recurso criado (ou `{ id }`) em `200`, com estrutura estável documentada.

---

## 4. Entrega em fases

### Fase 0 — Preparação (Front + alinhamento Back)

**Escopo**

- Criar/confirmar utilitário de contexto do fluxo (ler/gravar `produto`, `projeto`, `composicao`, `fluxo` na URL).
- Propagar query params em todos os `Link`/`navigate` do fluxo.
- Documentar contrato de create (seção 3.3) e validar com backend se `composicao-produtos/cadastrar` realmente devolve `id`.

**Critérios de aceite**

- [ ] Qualquer tela do fluxo iniciada com `?produto=7` mantém `produto=7` ao navegar para as próximas ações.
- [ ] Backend confirma formato de retorno do create de composição.

**Dependência backend:** confirmação do contrato de create; sem mudança de API ainda (exceto se o create já estiver inconsistente).

---

### Fase 1 — Orientação no hub `/fluxo-producao` (prioridade UX)

**Telas**

- `/fluxo-producao?etapa=1`
- `/fluxo-producao?produto={id}&etapa=2`
- `/fluxo-producao?etapa=3` (+ contexto)

**Requisitos**

#### 1.1 Etapa 1 — “Comece aqui”

- O botão primário **Cadastrar produto** deve exibir numeração/orientação, por exemplo:
  - badge `Passo 1` / texto `Comece aqui · Cadastrar produto`
- Texto curto acima das ações: “Para iniciar o fluxo, cadastre o produto base.”
- Botão secundário “Ver produtos” permanece, porém visualmente subordinado.

#### 1.2 Após cadastro do produto → Etapa 2

Comportamento desejado (já parcialmente implementado):

- Redirect: `/fluxo-producao?produto={id}&etapa=2`

Melhorias de UI:

- **Checklist / vertical stepper** (ou barra de progresso) com pelo menos:
  1. ✅ Produto cadastrado — mostrar nome/SKU/#id + link “Abrir produto”
  2. ○ Cadastrar ou escolher projeto
  3. ○ Criar vínculo produto–projeto
  4. ○ Configurar partes
  5. ○ Criar montagem
- Texto explícito do próximo passo:
  - **Opção A:** “Próximo: cadastrar um projeto de impressão”
  - **Opção B:** “Já tem projeto? Continuar para criar o vínculo”
- Botões:
  - Primário: `Passo 2.1 · Cadastrar projeto` → `/projetos-impressao/add?produto={id}&fluxo=1`
  - Secundário: `Passo 2.2 · Criar vínculo` → `/composicao-produtos/add?produto={id}&fluxo=1`
  - Terciário: Ver vínculos (listagem)

#### 1.3 Etapa 3 no hub

- Mesmo padrão de checklist: marcar E1/E2 concluídos quando houver contexto.
- Primário: `Passo 3 · Criar montagem` → `/grade-produtos/add?produto={id}&fluxo=1` (e `composicao` se disponível).

**Critérios de aceite**

- [ ] Em `etapa=1`, fica óbvio que o início é cadastrar produto.
- [ ] Em `etapa=2` com `produto`, o produto aparece como concluído (check).
- [ ] Usuário entende se deve cadastrar projeto **ou** ir direto ao vínculo.
- [ ] Em `etapa=3`, o histórico concluído permanece visível.

**Dependência backend:** nenhuma obrigatória (usa ids da URL + opcionalmente `GET produtos/view`).

---

### Fase 2 — Continuidade do projeto e CTA no rodapé

**Telas**

- `/projetos-impressao/add`
- `/projetos-impressao/view/{id}`

**Requisitos**

1. Ao salvar projeto novo no fluxo, redirect permanece em `/projetos-impressao/view/{id}`, **preservando** `?produto={id}&fluxo=1` (via navigate com search ou state + sync para URL).
2. Banner/stepper: “Etapa 2 · Projeto cadastrado. Adicione partes/itens e depois crie o vínculo.”
3. Botão **Continuar: criar vínculo** (já existe no topo) deve **também aparecer no rodapé**, abaixo da tabela de partes/itens / resumo de custos.
4. Link do botão deve pré-carregar contexto:
   - `/composicao-produtos/add?produto={produto}&projeto={projetoId}&fluxo=1`

**Critérios de aceite**

- [ ] Após cadastrar projeto no fluxo, a view mostra CTA de vínculo no topo **e** no rodapé.
- [ ] Clique leva ao form de vínculo com query `produto` + `projeto`.

**Dependência backend:** create de projeto já deve retornar `id` (validar).

---

### Fase 3 — Formulário de vínculo com contexto pré-preenchido

**Tela**

- `/composicao-produtos/add?produto={id}&projeto={id}&fluxo=1`

**Requisitos**

1. Título/alerta:  
   `Etapa 2 · Passo 2.2 — Criar vínculo Produto–Projeto`  
   (ajustar numeração visual para o padrão escolhido no hub).
2. Campos:
   - **Produto base** pré-selecionado a partir de `produto` (já existe parcialmente via `?produto=`).
   - **Projeto de impressão** pré-selecionado a partir de `projeto` (**novo**; hoje só há preload via `location.state` em alguns casos).
3. Usuário só confirma/salva (pode alterar se quiser).
4. Após salvar com sucesso:
   - **Sempre** redirecionar para `/composicao-produtos/view/{idCriado}?produto=…&projeto=…&fluxo=1`
   - **Não** redirecionar para `/composicao-produtos` (listagem) quando estiver no fluxo guiado.
5. Se o create não retornar `id`, tratar como erro de contrato (toast + log); alinhar backend.

**Critérios de aceite**

- [ ] Entrando pelo fluxo completo, produto e projeto já vêm preenchidos.
- [ ] Salvando no fluxo, abre a **view do vínculo criado**, não a listagem.

**Dependência backend (bloqueante se falhar):** retorno estável de `id` em `composicao-produtos/cadastrar`.

---

### Fase 4 — View do vínculo: status das partes + CTA montagem

**Tela**

- `/composicao-produtos/view/{id}`

**Requisitos**

#### 4.1 Tabela de partes

Colunas mínimas:

| Coluna | Conteúdo |
|--------|----------|
| Parte | Nome |
| Status configuração | Badge: `Configurada` (sucesso) / `Pendente` (warning) — baseado em `configurada` |
| Itens | Qtd. itens da parte |
| Total de variações | Contagem de variações da parte (`total_variacoes`) |
| Ação | Configurar parte / Editar configuração |

> Hoje a coluna “Config. de impressão” mostra `quantidade_itens`, o que é confuso. Separar **itens** vs **variações** e o **status**.

#### 4.2 Após salvar configuração da parte

- Manter redirect para a view do vínculo (já ocorre), com contexto de fluxo na URL.
- Mensagem: “Parte salva. Configure as demais ou continue para montagem.”

#### 4.3 CTA Continuar montagem

- Manter botão no topo.
- **Duplicar no rodapé** (abaixo da tabela de partes):  
  `Continuar: montagem` → `/fluxo-producao?etapa=3&produto={id}&composicao={id}&fluxo=1`  
  ou direto `/grade-produtos/add?produto={id}&fluxo=1` (preferência: hub etapa 3 se quisermos reforçar o checklist; ou ir direto à montagem se o hub etapa 3 for só passagem — **decisão de produto**: recomenda-se hub etapa 3 com checklist e CTA primário para criar montagem).

**Critérios de aceite**

- [ ] Partes configuradas vs pendentes são óbvias na tabela.
- [ ] Coluna com total de variações por parte.
- [ ] CTA de montagem visível no rodapé.

**Dependência backend (desejável):**

- Expor `total_variacoes` (e idealmente `configurada`) por parte no view da composição.
- Enquanto não houver, o front pode calcular `total_variacoes` a partir de `variacoes_itens` já retornados.

---

### Fase 5 — Montagem com produto pré-setado + checklist etapa 3

**Telas**

- `/fluxo-producao?etapa=3&produto={id}`
- `/grade-produtos/add?produto={id}&fluxo=1`

**Requisitos**

1. Hub etapa 3 com vertical stepper/checklist completo do que já foi feito.
2. Ao clicar em criar montagem, abrir `/grade-produtos/add` com **produto base pré-selecionado** (já existe lógica `precarregarProdutoDoFluxo` — garantir que todos os links do fluxo passam `?produto=`).
3. Banner: `Etapa 3 · Criar montagem do produto #{id}`.

**Critérios de aceite**

- [ ] Campo produto já vem preenchido quando o usuário veio do fluxo.
- [ ] Checklist da etapa 3 mostra histórico das etapas anteriores.

**Dependência backend:** nenhuma obrigatória além dos GETs já existentes.

---

### Fase 6 (opcional / backend) — Endpoint de progresso do fluxo

**Proposta**

`GET /fluxo-producao/progresso?produto={id}`  
ou embutido em view de produto / composição.

Retorna subpassos da seção 3.2 + ids relacionados (`projeto_id`, `composicao_id`, `grade_id`) + resumo de partes.

**Benefício:** hub e stepper passam a refletir progresso real mesmo se o usuário sair e voltar sem query params completos.

---

## 5. Mapa de navegação desejado (fluxo feliz)

```text
[Hub etapa 1]
  └─ Passo 1: Cadastrar produto
       └─ POST produtos → redirect Hub etapa 2 (?produto=)
            ├─ Passo 2.1: Cadastrar projeto (?produto=)
            │     └─ View projeto (partes/itens)
            │           └─ Continuar vínculo (topo + rodapé)
            │                 └─ /composicao-produtos/add?produto=&projeto=
            └─ (atalho) Passo 2.2: Criar vínculo (?produto=) [projeto manual]
                  └─ Salvar vínculo → SEMPRE view vínculo (?produto=&projeto=&fluxo=)
                        ├─ Configurar parte(s) → volta para view vínculo
                        └─ Continuar montagem (topo + rodapé)
                              └─ Hub etapa 3 (?produto=&composicao=)
                                    └─ Criar montagem /grade-produtos/add?produto=
```

---

## 6. Alterações previstas por área (frontend)

| Área | Arquivos / pontos prováveis |
|------|-----------------------------|
| Hub | `FluxoProducaoPage.tsx`, `fluxoProducaoConfig.ts` |
| Stepper global | `FluxoProducaoStepper.tsx` (opcional: marcar etapas concluídas) |
| Produto | `ProdutosForm.tsx` (manter redirect etapa 2; preservar `fluxo`) |
| Projeto | `ProjetosImpressaoForm.tsx`, `ProjetosImpressaoView.tsx` (query + CTA rodapé) |
| Vínculo form | `ComposicaoProdutosForm.tsx` (preload `projeto`, banner, redirect view) |
| Vínculo view | `ComposicaoProdutosView.tsx`, `useComposicaoProdutos.ts` (status, variações, CTA rodapé) |
| Montagem | `GradeProdutosForm.tsx` (garantir preload), links do hub |
| Contexto | novo helper, ex.: `fluxoProducaoContext.ts` (montar/ler query) |

---

## 7. Checklist de alinhamento backend (para o time da API)

- [ ] `composicao-produtos/cadastrar` sempre retorna `id` do vínculo criado.
- [ ] Demais creates do fluxo retornam `id` de forma consistente.
- [ ] (Fase 4+) View da composição inclui por parte: `configurada`, `total_variacoes` (e opcionalmente contadores auxiliares).
- [x] (Fase 6) Endpoint ou payload de progresso do fluxo por `produto_id`.
- [x] Confirmar regra de negócio: montagem exige **todas** as partes configuradas ou permite parcial? **Exige todas** (B3).

---

## 8. Ordem sugerida de implementação

| Ordem | Fase | Esforço relativo | Valor para o usuário |
|------:|------|------------------|----------------------|
| 1 | Fase 0 — contexto na URL | Baixo | Base para tudo |
| 2 | Fase 1 — hub orientado | Médio | “Sei por onde começar” |
| 3 | Fase 3 — preload + redirect view | Baixo/Médio | Continuidade sem retrabalho |
| 4 | Fase 2 — CTA rodapé projeto | Baixo | Menos perda na view longa |
| 5 | Fase 4 — status partes + CTA | Médio | Clareza do que falta configurar |
| 6 | Fase 5 — montagem + checklist 3 | Baixo | Fecha o fluxo |
| 7 | Fase 6 — progresso API | Médio (back) | Robustez ao retomar fluxo |

---

## 9. Fora de escopo (nesta especificação)

- Redesign visual completo do design system.
- Mudança de nomenclatura de rotas legadas (`composicao-produtos`, `grade-produtos`).
- Automação completa “wizard em uma única página” (mantém-se o hub + telas CRUD existentes, com orientação).

---

## 10. Referência rápida das URLs citadas

- Hub etapa 1: `http://10.0.0.164:3000/fluxo-producao?etapa=1`
- Hub etapa 2: `http://10.0.0.164:3000/fluxo-producao?produto=7&etapa=2`
- Criar vínculo: `http://10.0.0.164:3000/composicao-produtos/add`
- Listagem vínculos: `http://10.0.0.164:3000/composicao-produtos`
- View vínculo: `http://10.0.0.164:3000/composicao-produtos/view/6`
- Configurar parte: `http://10.0.0.164:3000/composicao-produtos/6/parte/16/configurar`
- Hub etapa 3: `http://10.0.0.164:3000/fluxo-producao?etapa=3`
- Criar montagem: `http://10.0.0.164:3000/grade-produtos/add`
