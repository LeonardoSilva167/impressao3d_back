<?php

namespace App\Http\Controllers;

use App\Services\RequestDataService;
use App\Services\SubtipoProduto\SubtipoProdutoService;
use Exception;
use Illuminate\Http\Request;

class SubtipoProdutoController extends Controller
{
       /**
     * @var SubtipoProdutoService $_service
     */
    private SubtipoProdutoService $_service;
    
    /**
     * @var RequestDataService
     */
    protected $_requestService;

    public function __construct(){
        $this->_service = new SubtipoProdutoService();
        $this->_requestService = new RequestDataService();
    }


    public function listarLookupSubtipoProduto()
    {
        try {
            $result = $this->_service->handleLookupsSubtipoProduto();
    
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createSubtipoProduto(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleAddSubtipoProduto($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editSubtipoProduto(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleEditSubtipoProduto($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteSubtipoProduto($id_SubtipoProduto)
    {
        try {
            $result = $this->_service->handleDeleteSubtipoProduto($id_SubtipoProduto);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarSubtipoProduto(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result = $this->_service->getSubtipoProdutoPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarSubtipoProdutoId(string $id_SubtipoProduto)
    {
        try {
            $result = $this->_service->getSubtipoProdutoId($id_SubtipoProduto);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarSubtipoProdutoAsync(Request $request)
    {
        try {
            $params = (object)$request->all();
            $result = $this->_service->getSubtipoProdutoAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
    
}
