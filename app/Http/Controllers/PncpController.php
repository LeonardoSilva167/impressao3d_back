<?php

namespace App\Http\Controllers;

use App\Services\Pncp\PncpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PncpController extends Controller
{
       /**
     * @var PncpService $_service
     */
    private PncpService $_service;
    

    public function __construct(){
        $this->_service = new PncpService();
    }

    public function buscarEdital(Request $request)
    {
        try {
            // $result = $this->_service->handleBuscarEdital();
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleBuscarEdital($objectAtributes);
    
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function buscarEdital_old(Request $request)
    {
        $request->validate([
            'link' => ['required', 'url']
        ]);

        preg_match(
            '#/editais/(\d+)/(\d{4})/(\d+)#',
            $request->link,
            $matches
        );

        if (count($matches) !== 4) {
            return response()->json([
                'message' => 'Link do PNCP inválido'
            ], 422);
        }

        [, $cnpj, $ano, $sequencial] = $matches;

        // remove zeros à esquerda (000126 -> 126)
        $sequencial = ltrim($sequencial, '0');

        $url = "https://pncp.gov.br/api/consulta/v1/orgaos/{$cnpj}/compras/{$ano}/{$sequencial}";

        $response = Http::withHeaders([
            'Accept'     => 'application/json, text/plain, */*',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Referer'    => 'https://pncp.gov.br/',
            'Origin'     => 'https://pncp.gov.br',
        ])->timeout(20)->get($url);

        if ($response->status() === 403) {
            return response()->json([
                'message' => 'Acesso bloqueado pelo PNCP (headers insuficientes)',
                'url' => $url
            ], 403);
        }

        if ($response->status() === 404) {
            return response()->json([
                'message' => 'Compra não encontrada no PNCP',
                'url' => $url
            ], 404);
        }

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Erro ao consultar PNCP',
                'status' => $response->status()
            ], 422);
        }

        $data = $response->json();

        return response()->json([
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
        ]);
    }
}
