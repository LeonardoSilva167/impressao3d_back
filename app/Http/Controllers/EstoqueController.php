<?php

namespace App\Http\Controllers;

use App\Http\Requests\Estoque\EstoqueConsumirRequest;
use App\Http\Requests\Estoque\EstoqueFinalizarCarretelRequest;
use App\Services\Estoque\LoteService;
use App\Services\Estoque\MovimentacaoEstoqueService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    /**
     * @var LoteService $_loteService
     */
    private LoteService $_loteService;

    /**
     * @var MovimentacaoEstoqueService $_movimentacaoService
     */
    private MovimentacaoEstoqueService $_movimentacaoService;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_loteService         = new LoteService();
        $this->_movimentacaoService = new MovimentacaoEstoqueService();
        $this->_requestService      = new RequestDataService();
    }

    public function listarLotes(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_loteService->getLotesPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function consumirFilamento(EstoqueConsumirRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_movimentacaoService->handleConsumirFilamento($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function finalizarCarretel(EstoqueFinalizarCarretelRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_movimentacaoService->handleFinalizarCarretel($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
