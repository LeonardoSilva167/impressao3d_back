<?php

namespace App\Services\Pncp;

use App\Services\Licitacoes\LicitacoesService;
use App\Utils\PncpLinkParser;
use Illuminate\Support\Facades\Http;

class PncpService
{
    protected $_service_licitacoes;
    public function __construct()
    {
        $this->_service_licitacoes = new LicitacoesService();
    }

    public function handleBuscarEdital(object $attributes)
    {

        $parsed = PncpLinkParser::parse($attributes->link);

        $data = $this->consultarPncp(
            $parsed['cnpj'],
            $parsed['ano'],
            $parsed['sequencial']
        );

        $consultaEdital = $this->mapearResposta($data);

        $params = (object)[];

        $params->modalidade = $consultaEdital['modalidade']['modalidadeId'] ?? null;
        $params->num_compra = $consultaEdital['compra']['numeroCompra'] ?? null;
        $params->exercicio = $consultaEdital['compra']['anoCompra'] ?? null;
        $params->codigo = $consultaEdital['unidade_compradora']['codigo'] ?? null;
        $consultaEdital['proposta'] = $this->_service_licitacoes->getLicitacaoData($params);

        return $consultaEdital;
    }

    private function consultarPncp(string $cnpj, string $ano, string $sequencial): array
    {
        $url = "https://pncp.gov.br/api/consulta/v1/orgaos/{$cnpj}/compras/{$ano}/{$sequencial}";

        $response = Http::withHeaders([
            'Accept'     => 'application/json, text/plain, */*',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Referer'    => 'https://pncp.gov.br/',
            'Origin'     => 'https://pncp.gov.br',
        ])->timeout(20)->get($url);

        if ($response->status() === 403) {
            throw new \Exception('Acesso bloqueado pelo PNCP (headers insuficientes)', 403);
        }

        if ($response->status() === 404) {
            throw new \Exception('Compra não encontrada no PNCP', 404);
        }

        if (!$response->successful()) {
            throw new \Exception('Erro ao consultar PNCP', 422);
        }

        return $response->json();
    }

    private function mapearResposta(array $data): array
    {
        return [
            'orgao' => [
                'cnpj' => $data['orgaoEntidade']['cnpj'] ?? null,
                'nome' => $data['orgaoEntidade']['razaoSocial'] ?? null,
            ],
            'local' => [
                'municipio' => $data['unidadeOrgao']['municipioNome'] ?? null,
                'uf' => $data['unidadeOrgao']['ufSigla'] ?? null,
            ],
            'unidade_compradora' => [
                'codigo' => $data['unidadeOrgao']['codigoUnidade'] ?? null,
                'nome' => $data['unidadeOrgao']['nomeUnidade'] ?? null,
                'uf' => $data['unidadeOrgao']['ufSigla'] ?? null,
                'cidade' => $data['unidadeOrgao']['municipioNome'] ?? null,
            ],
            'datas' => [
                'abertura_propostas' => $data['dataAberturaProposta'] ?? null,
                'fim_recebimento_propostas' => $data['dataEncerramentoProposta'] ?? null,
            ],
            'compra' => [
                'numeroCompra' => $data['numeroCompra'] ?? null,
                'anoCompra' => $data['anoCompra'] ?? null,
            ],
            'modalidade' => [
                'modalidade' => $data['modalidade'] ?? null,
                'modalidadeId' => $data['modalidadeId'] ?? null,
            ],
            'objeto' => $data['objetoCompra'] ?? null,
            'situacao' => $data['situacaoCompraNome'] ?? null,
            'valor_estimado' => $data['valorTotalEstimado'] ?? null,
        ];
    }
    // public function buscarCompra(string $cnpj, string $ano, string $sequencial): array
    // {
    //     $url = "https://pncp.gov.br/api/consulta/v1/orgaos/{$cnpj}/compras/{$ano}/{$sequencial}";

    //     $response = Http::withHeaders([
    //         'Accept'     => 'application/json, text/plain, */*',
    //         'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    //         'Referer'    => 'https://pncp.gov.br/',
    //         'Origin'     => 'https://pncp.gov.br',
    //     ])->timeout(20)->get($url);

    //     if ($response->status() === 403) {
    //         throw new \Exception('Acesso bloqueado pelo PNCP (headers insuficientes)', 403);
    //     }

    //     if ($response->status() === 404) {
    //         throw new \Exception('Compra não encontrada no PNCP', 404);
    //     }

    //     if (!$response->successful()) {
    //         throw new \Exception('Erro ao consultar PNCP', 422);
    //     }

    //     return $response->json();
    // }
}
