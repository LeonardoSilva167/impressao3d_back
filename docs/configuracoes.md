# Módulo: Configurações

## Descrição

Armazena configurações globais do sistema. Utilizado internamente pelos serviços e exposto via API para consulta e edição.

---

## Campos

| Campo               | Tipo    | Valor inicial | Descrição                                      |
|---------------------|---------|---------------|------------------------------------------------|
| proximo_codigo_base | integer | 1000          | Próximo código a ser atribuído a um produto base |
| custo_energia_kwh   | decimal | 1.039         | Custo de energia por kWh (hora de impressão)     |
| custo_desgaste_hora | decimal | 1.20          | Custo de desgaste por hora de impressão          |

---

## Rotas

| Método | Rota                              | Descrição                    |
|--------|-----------------------------------|------------------------------|
| GET    | `/api/v1/configuracoes/listar/{id}` | Busca configuração por ID  |
| PUT    | `/api/v1/configuracoes/editar`      | Atualiza configuração      |

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
