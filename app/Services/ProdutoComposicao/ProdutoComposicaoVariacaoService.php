<?php

namespace App\Services\ProdutoComposicao;

use App\Models\ProdutoVariacao;
use App\Repositories\Filamento\FilamentoRepository;
use App\Repositories\Configuracao\ConfiguracaoRepository;
use App\Repositories\ProdutoComposicaoCor\ProdutoComposicaoCorRepository;
use App\Repositories\ProdutoVariacao\ProdutoVariacaoRepository;
use App\Repositories\ProdutoVariacaoFilamento\ProdutoVariacaoFilamentoRepository;
use App\Services\Filamento\FilamentoService;
use App\Services\Custo\CustoCalculoService;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCalculoService;
use Exception;
use Illuminate\Support\Facades\DB;

class ProdutoComposicaoVariacaoService
{
    private ProdutoVariacaoRepository $_variacaoRepository;

    private ProdutoVariacaoFilamentoRepository $_filamentoRepository;

    private ProdutoComposicaoCorRepository $_corRepository;

    private FilamentoRepository $_filamentoRepo;

    private ProdutoComposicaoCalculoService $_calculoService;

    private ProjetoImpressaoParteItemCalculoService $_itemCalculoService;

    private ConfiguracaoRepository $_configuracaoRepository;

    private CustoCalculoService $_custoService;

    public function __construct()
    {
        $this->_variacaoRepository      = new ProdutoVariacaoRepository();
        $this->_filamentoRepository     = new ProdutoVariacaoFilamentoRepository();
        $this->_corRepository           = new ProdutoComposicaoCorRepository();
        $this->_filamentoRepo           = new FilamentoRepository();
        $this->_calculoService          = new ProdutoComposicaoCalculoService();
        $this->_itemCalculoService      = new ProjetoImpressaoParteItemCalculoService();
        $this->_configuracaoRepository  = new ConfiguracaoRepository();
        $this->_custoService            = new CustoCalculoService();
    }

    public function gerarVariacoesPreview(int $idComposicao, ?int $idParte = null, ?int $idItemProjeto = null): array
    {
        $coresConfig = $this->_corRepository->getByComposicaoId($idComposicao, $idParte);

        if ($idItemProjeto !== null) {
            $coresConfig = $coresConfig->where('id_item_projeto', $idItemProjeto)->values();
        }

        if ($coresConfig->isEmpty()) {
            throw new Exception('Nenhuma cor configurada para gerar variações.', 422);
        }

        $variacoes = $coresConfig
            ->map(fn ($cor) => $this->mapCorParaVariacaoPreview($cor))
            ->values()
            ->toArray();

        return [
            'id_composicao'   => $idComposicao,
            'id_parte'        => $idParte,
            'id_item_projeto' => $idItemProjeto,
            'total_variacoes' => count($variacoes),
            'itens'           => ProdutoComposicaoCorMapper::agruparVariacoesPorItem($variacoes),
            'variacoes'       => $variacoes,
        ];
    }

    public function confirmarVariacoes(int $idComposicao, ?int $idParte = null, ?int $idItemProjeto = null): object
    {
        $preview = $this->gerarVariacoesPreview($idComposicao, $idParte, $idItemProjeto);

        if ($idItemProjeto !== null) {
            $this->removerVariacoesItem($idComposicao, $idItemProjeto);
        } elseif ($idParte !== null) {
            $this->_filamentoRepository->deleteByParteId($idComposicao, $idParte);
            $this->_variacaoRepository->deleteByParteId($idComposicao, $idParte);
        } else {
            $this->_filamentoRepository->deleteByComposicaoId($idComposicao);
            $this->_variacaoRepository->deleteByComposicaoId($idComposicao);
        }

        foreach ($preview['variacoes'] as $variacao) {
            $this->_variacaoRepository->create([
                'id_composicao'     => $idComposicao,
                'id_parte'          => $variacao['id_parte'],
                'id_item_projeto'   => $variacao['id_item_projeto'],
                'tipo_cor'          => $variacao['tipo_cor'],
                'id_cor'            => $variacao['id_cor'],
                'id_composicao_cor' => $variacao['id_composicao_cor'],
            ]);
        }

        return (object) [
            'data'    => [
                'id_composicao'   => $idComposicao,
                'id_parte'        => $idParte,
                'id_item_projeto' => $idItemProjeto,
                'total_variacoes' => count($preview['variacoes']),
                'itens'           => ProdutoComposicaoCorMapper::agruparVariacoesPorItem(
                    $this->mapVariacoesComFilamentos($idComposicao, $idParte, $idItemProjeto)
                ),
                'variacoes'       => $this->mapVariacoesComFilamentos($idComposicao, $idParte, $idItemProjeto),
            ],
            'status'  => true,
            'message' => 'Variações dos itens confirmadas e salvas com sucesso!',
        ];
    }

    public function salvarFilamentos(int $idComposicao, array $filamentosPayload, ?int $idParte = null, ?int $idItemProjeto = null): object
    {
        $variacoes = $this->_variacaoRepository->getByComposicaoId($idComposicao, $idParte);

        if ($idItemProjeto !== null) {
            $variacoes = $variacoes->where('id_item_projeto', $idItemProjeto)->values();
        }

        if ($variacoes->isEmpty()) {
            throw new Exception('Nenhuma variação confirmada. Confirme as variações dos itens antes de salvar filamentos.', 422);
        }

        $variacoesPorId = $variacoes->keyBy('id');

        if (count($filamentosPayload) !== $variacoes->count()) {
            throw new Exception('Informe filamentos para todas as variações confirmadas.', 422);
        }

        $idsProcessados = [];
        $configCustos   = $this->_configuracaoRepository->getCustosConfig();
        $temposPorItem  = $this->carregarTemposItensPorVariacoes($variacoes);

        foreach ($filamentosPayload as $payload) {
            $payload    = (object) $payload;
            $idVariacao = (int) $payload->id_variacao;

            if (!isset($variacoesPorId[$idVariacao])) {
                throw new Exception('Variação informada não pertence a esta composição.', 422);
            }

            if (in_array($idVariacao, $idsProcessados, true)) {
                throw new Exception('Variação duplicada no payload de filamentos.', 422);
            }

            $idsProcessados[] = $idVariacao;

            $filamento = $this->_filamentoRepo->findByIdWithRelations((int) $payload->id_filamento);

            if (!$filamento) {
                throw new Exception('Filamento informado não encontrado.', 422);
            }

            $pesoItem = $this->_itemCalculoService->normalizarPeso(
                $payload->peso_item ?? null,
                'O peso do item',
                true
            );

            $precoMedioGrama = isset($payload->preco_medio_grama)
                ? round((float) $payload->preco_medio_grama, 4)
                : FilamentoService::resolverPrecoMedioPorGrama(
                    isset($filamento->preco_medio_grama) ? (float) $filamento->preco_medio_grama : null,
                    isset($filamento->item_preco_medio_atual) ? (float) $filamento->item_preco_medio_atual : null,
                    !empty($filamento->id_item) ? (int) $filamento->id_item : null,
                );


            $variacao       = $variacoesPorId[$idVariacao];
            $tempoImpressao = $temposPorItem[(int) $variacao->id_item_projeto] ?? '00:00';

            $custos = $this->_custoService->calcularCustosCompletos(
                $pesoItem,
                $precoMedioGrama,
                $tempoImpressao,
                $configCustos['custo_energia_kwh'],
                $configCustos['custo_desgaste_hora'],
            );

            $this->_filamentoRepository->create([
                'id_variacao'       => $idVariacao,
                'id_filamento'      => (int) $payload->id_filamento,
                'preco_medio_grama' => $precoMedioGrama,
                'peso_item'         => $pesoItem,
                'custo_filamento'   => $custos['custo_filamento'],
                'custo_energia'     => $custos['custo_energia'],
                'custo_desgaste'    => $custos['custo_desgaste'],
                'custo_total'       => $custos['custo_total'],
            ]);
        }

        return (object) [
            'data'    => $this->mapVariacoesComFilamentos($idComposicao, $idParte, $idItemProjeto),
            'status'  => true,
            'message' => 'Filamentos das variações salvos com sucesso!',
        ];
    }

    public function mapVariacoesComFilamentos(int $idComposicao, ?int $idParte = null, ?int $idItemProjeto = null): array
    {
        $variacoes = $this->_variacaoRepository->getByComposicaoId($idComposicao, $idParte);

        if ($idItemProjeto !== null) {
            $variacoes = $variacoes->where('id_item_projeto', $idItemProjeto)->values();
        }

        return $variacoes
            ->map(fn ($variacao) => $this->mapVariacaoDetalhe($variacao))
            ->values()
            ->toArray();
    }

    private function removerVariacoesItem(int $idComposicao, int $idItemProjeto): void
    {
        $idsVariacao = $this->_variacaoRepository->getByComposicaoId($idComposicao)
            ->where('id_item_projeto', $idItemProjeto)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->_filamentoRepository->deleteByVariacaoIds($idsVariacao);

        foreach ($idsVariacao as $idVariacao) {
            $variacao = $this->_variacaoRepository->findById($idVariacao);

            if ($variacao) {
                $variacao->delete();
            }
        }
    }

    private function mapCorParaVariacaoPreview(object $cor): array
    {
        return [
            'id_composicao_cor'  => (int) $cor->id,
            'id_parte'           => (int) $cor->id_parte,
            'id_item_projeto'    => (int) $cor->id_item_projeto,
            'nome_parte'         => $cor->nome_parte,
            'nome_item'          => $cor->nome_item,
            'tipo_cor'           => $cor->tipo_cor,
            'id_cor'             => (int) $cor->id_cor,
            'descricao_variacao' => $cor->nome_item . ' - ' . $cor->cor_descricao,
            'cor'                => [
                'descricao'   => $cor->cor_descricao,
                'codigo'      => $cor->cor_codigo,
                'hexadecimal' => $cor->cor_hexadecimal ?? null,
            ],
        ];
    }

    private function mapVariacaoDetalhe(object $variacao): array
    {
        $result = [
            'id'                 => (int) $variacao->id,
            'id_parte'           => (int) $variacao->id_parte,
            'id_item_projeto'    => (int) $variacao->id_item_projeto,
            'nome_parte'         => $variacao->nome_parte,
            'nome_item'          => $variacao->nome_item,
            'tipo_cor'           => $variacao->tipo_cor,
            'id_cor'             => (int) $variacao->id_cor,
            'descricao_variacao' => $variacao->nome_item . ' - ' . $variacao->cor_descricao,
            'cor'                => [
                'descricao'   => $variacao->cor_descricao,
                'codigo'      => $variacao->cor_codigo,
                'hexadecimal' => $variacao->cor_hexadecimal ?? null,
            ],
            'custo_filamento'  => round((float) ($variacao->custo_filamento ?? 0), 4),
            'custo_energia'    => round((float) ($variacao->custo_energia ?? 0), 4),
            'custo_desgaste'   => round((float) ($variacao->custo_desgaste ?? 0), 4),
            'custo_total'      => round((float) ($variacao->custo_total ?? 0), 4),
            'filamento'        => null,
        ];

        if (!empty($variacao->id_filamento)) {
            $result['filamento'] = [
                'id'                => (int) $variacao->id_filamento,
                'resumo'            => $variacao->filamento_resumo,
                'preco_medio_grama' => round((float) $variacao->preco_medio_grama, 4),
                'peso_item'         => round((float) $variacao->peso_item, 2),
                'custo_filamento'   => round((float) $variacao->custo_filamento, 4),
                'custo_energia'     => round((float) ($variacao->custo_energia ?? 0), 4),
                'custo_desgaste'    => round((float) ($variacao->custo_desgaste ?? 0), 4),
                'custo_total'       => round((float) ($variacao->custo_total ?? 0), 4),
            ];
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function carregarTemposItensPorVariacoes($variacoes): array
    {
        $idsItens = $variacoes->pluck('id_item_projeto')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (empty($idsItens)) {
            return [];
        }

        return DB::table('projetos_impressao_parte_itens')
            ->whereIn('id', $idsItens)
            ->whereNull('deleted_at')
            ->pluck('tempo_impressao', 'id')
            ->map(fn ($tempo) => (string) $tempo)
            ->toArray();
    }
}
