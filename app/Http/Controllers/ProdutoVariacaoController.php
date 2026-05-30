<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProdutoVariacao\ProdutoVariacaoCadastrarRequest;
use App\Http\Requests\ProdutoVariacao\ProdutoVariacaoEditarRequest;
use App\Services\ProdutoVariacao\ProdutoVariacaoService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class ProdutoVariacaoController extends Controller
{
    private ProdutoVariacaoService $_service;
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new ProdutoVariacaoService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsProdutoVariacao()
    {
        try {
            $result = $this->_service->handleLookupsProdutoVariacao();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProdutoVariacao(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getProdutoVariacaoPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProdutoVariacaoId(string $id)
    {
        try {
            $result = $this->_service->getProdutoVariacaoId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createProdutoVariacao(ProdutoVariacaoCadastrarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleAddProdutoVariacao($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editProdutoVariacao(ProdutoVariacaoEditarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleEditProdutoVariacao($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteProdutoVariacao(string $id)
    {
        try {
            $result = $this->_service->handleDeleteProdutoVariacao($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProdutoVariacaoAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getProdutoVariacaoAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
