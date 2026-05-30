<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinhaProduto\LinhaProdutoCadastrarRequest;
use App\Http\Requests\LinhaProduto\LinhaProdutoEditarRequest;
use App\Services\LinhaProduto\LinhaProdutoService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class LinhaProdutoController extends Controller
{
    /**
     * @var LinhaProdutoService $_service
     */
    private LinhaProdutoService $_service;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new LinhaProdutoService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsLinhaProduto()
    {
        try {
            $result = $this->_service->handleLookupsLinhaProduto();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLinhaProduto(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getLinhaProdutoPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLinhaProdutoId(string $id)
    {
        try {
            $result = $this->_service->getLinhaProdutoId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createLinhaProduto(LinhaProdutoCadastrarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleAddLinhaProduto($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editLinhaProduto(LinhaProdutoEditarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleEditLinhaProduto($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteLinhaProduto(string $id)
    {
        try {
            $result = $this->_service->handleDeleteLinhaProduto($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLinhaProdutoAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getLinhaProdutoAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
