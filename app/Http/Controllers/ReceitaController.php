<?php

namespace App\Http\Controllers;

use App\Services\Receita\ReceitaService;
use App\Services\Receitas\ReceitasService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class ReceitaController extends Controller
{
       /**
     * @var ReceitasService $_receitaService
     */
    private ReceitasService $_service;
    
    /**
     * @var RequestDataService
     */
    protected $_requestService;

    public function __construct(){
        $this->_service = new ReceitasService();
        $this->_requestService = new RequestDataService();
    }


    public function listarLookupsReceitas()
    {
        try {
            $result = $this->_service->handleLookupsReceitas();
    
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }


    public function createReceitas(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleAddReceitas($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editReceitas(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result = $this->_service->handleEditReceitas($objectAtributes);
            return response()->json($result, 200);
        } catch (\Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteReceitas($id_Receita)
    {
        try {
            $result = $this->_service->handleDeleteReceitas($id_Receita);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarReceitas(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result = $this->_service->getReceitasPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
    
    public function listarReceitasId(string $id_Receita)
    {
        try {
            $result = $this->_service->getReceitasId($id_Receita);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarReceitasAsync(Request $request)
    {
        try {
            $params = (object)$request->all();
            $result = $this->_service->getReceitasAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
    
}
