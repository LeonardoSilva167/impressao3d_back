<?php

namespace App\Http\Controllers;

use App\Http\Requests\Licitacoes\LicitacoesListaRequest;
use App\Services\Licitacoes\LicitacoesService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class LicitacoesController extends Controller
{
       /**
     * @var Licitacoeservice $_service
     */
    private LicitacoesService $_service;
    
    /**
     * @var RequestDataService
     */
    protected $_requestService;

    public function __construct(){
        $this->_service = new LicitacoesService();
        $this->_requestService = new RequestDataService();
    }


    public function listarLookupsLicitacoes()
    {
        try {
            $result = $this->_service->handleLookupsLicitacoes();
    
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createLicitacoes(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleAddLicitacoes($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editLicitacoes(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleEditLicitacoes($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteLicitacoes($id_cliente)
    {
        try {
            $result = $this->_service->handleDeleteLicitacoes($id_cliente);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLicitacoes(LicitacoesListaRequest $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result = $this->_service->getLicitacoesPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLicitacoesId(string $id_cliente)
    {
        try {
            $result = $this->_service->getLicitacoesId($id_cliente);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLicitacoesAsync(Request $request)
    {
        try {
            $params = (object)$request->all();
            $result = $this->_service->getLicitacoesAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
 
    
    public function listarLookupsLicitacoesItens()
    {
        try {
            $result = $this->_service->handleLookupsLicitacoesItens();
    
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createLicitacoesItens(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleAddLicitacoes($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editLicitacoesItens(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleEditLicitacoes($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteLicitacoesItens($id_cliente)
    {
        try {
            $result = $this->_service->handleDeleteLicitacoesItens($id_cliente);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLicitacoesItens(LicitacoesListaRequest $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result = $this->_service->getLicitacoesItensPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLicitacoesItensId(string $id_cliente)
    {
        try {
            $result = $this->_service->getLicitacoesItensId($id_cliente);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLicitacoesItensAsync(Request $request)
    {
        try {
            $params = (object)$request->all();
            $result = $this->_service->getLicitacoesAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
