<?php

namespace App\Http\Controllers;

use App\Services\Compra\CompraAnaliseService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class CompraAnaliseController extends Controller
{
    /**
     * @var CompraAnaliseService $_service
     */
    private CompraAnaliseService $_service;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new CompraAnaliseService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsCompraAnalise()
    {
        try {
            $result = $this->_service->handleLookupsCompraAnalise();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarCompraAnalise(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->handleAnaliseCompras($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
