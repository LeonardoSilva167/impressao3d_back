<?php

namespace App\Http\Controllers;

use App\Services\FluxoProducao\FluxoProducaoService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class FluxoProducaoController extends Controller
{
    private FluxoProducaoService $_service;

    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new FluxoProducaoService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsFluxoProducao()
    {
        try {
            $result = $this->_service->handleLookupsFluxoProducao();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function obterProgresso(Request $request)
    {
        try {
            $params = (object) [
                'produto'    => $request->query('produto'),
                'projeto'    => $request->query('projeto'),
                'composicao' => $request->query('composicao'),
            ];

            $result = $this->_service->getProgresso($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    private function respondError(Exception $ex)
    {
        $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
        $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;

        return response()->json([
            'error'   => true,
            'message' => $ex->getMessage(),
        ], $statusCode);
    }
}
