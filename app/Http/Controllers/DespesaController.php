<?php

namespace App\Http\Controllers;

use App\Services\Despesa\DespesaService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class DespesaController extends Controller
{
       /**
     * @var DespesaService $_despesaService
     */
    private DespesaService $_service;
    
    /**
     * @var RequestDataService
     */
    protected $_requestService;

    public function __construct(){
        $this->_service = new DespesaService();
        $this->_requestService = new RequestDataService();
    }


    public function listarLookupsDespesas()
    {
        try {
            $result = $this->_service->handleLookupsDespesas();
    
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }


    public function createDespesas(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleAddDespesa($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editDespesas(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleEditDespesa($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteDespesas($id_despesa)
    {
        try {
            $result = $this->_service->handleDeleteDespesa($id_despesa);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarDespesas(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result = $this->_service->getDespesasPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarDespesaId(string $id_despesa)
    {
        try {
            $result = $this->_service->getDespesaId($id_despesa);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarDespesasAsync(Request $request)
    {
        try {
            $params = (object)$request->all();
            $result = $this->_service->getDespesasAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
    
}
