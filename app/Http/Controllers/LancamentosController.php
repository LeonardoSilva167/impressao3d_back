<?php

namespace App\Http\Controllers;

use App\Services\Lancamentos\Lancamentoservice;
use App\Services\Lancamentos\LancamentosService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class LancamentosController extends Controller
{
       /**
     * @var Lancamentoservice $_Lancamentoservice
     */
    private LancamentosService $_service;
    
    /**
     * @var RequestDataService
     */
    protected $_requestService;

    public function __construct(){
        $this->_service = new LancamentosService();
        $this->_requestService = new RequestDataService();
    }


    public function listarLookupsLancamentos()
    {
        try {
            $result = $this->_service->handleLookupsLancamentos();
    
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createLancamentos(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleAddLancamentos($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editLancamentos(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleEditLancamentos($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteLancamentos($id_Lancamentos)
    {
        try {
            $result = $this->_service->handleDeleteLancamentos($id_Lancamentos);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLancamentos(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result = $this->_service->getLancamentosPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLancamentosId(string $id_Lancamentos)
    {
        try {
            $result = $this->_service->getLancamentosId($id_Lancamentos);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLancamentosAsync(Request $request)
    {
        try {
            $params = (object)$request->all();
            $result = $this->_service->getLancamentossAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
    
}
