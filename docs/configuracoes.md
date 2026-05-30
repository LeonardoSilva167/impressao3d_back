# Módulo: Configurações

## Descrição

Armazena configurações globais do sistema. Utilizado internamente pelos serviços (sem rotas públicas no momento).

---

## Campos

| Campo               | Tipo    | Valor inicial | Descrição                                      |
|---------------------|---------|---------------|------------------------------------------------|
| proximo_codigo_base | integer | 1000          | Próximo código a ser atribuído a um produto base |

---

## Funcionamento

Ao cadastrar um produto base:

1. Lê `proximo_codigo_base`
2. Atribui ao `codigo_base` do produto
3. Salva o produto
4. Incrementa `proximo_codigo_base` em +1

> Nunca calcular pelo maior código existente. Sempre usar esta tabela.

---

## Tabela

`configuracoes` — registro único seedado na migration com `proximo_codigo_base = 1000`.
