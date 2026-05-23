<?php

namespace App\Http\Controllers;

use App\Services\LinhaMarca\LinhaMarcaService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class LinhaMarcaController extends Controller
{
    /**
     * @var LinhaMarcaService $_service
     */
    private LinhaMarcaService $_service;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new LinhaMarcaService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsLinhaMarca()
    {
        try {
            $result = $this->_service->handleLookupsLinhaMarca();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLinhaMarca(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getLinhaMarcaPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLinhaMarcaId(string $id)
    {
        try {
            $result = $this->_service->getLinhaMarcaId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createLinhaMarca(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result          = $this->_service->handleAddLinhaMarca($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editLinhaMarca(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result          = $this->_service->handleEditLinhaMarca($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteLinhaMarca(string $id)
    {
        try {
            $result = $this->_service->handleDeleteLinhaMarca($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLinhaMarcaAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getLinhaMarcaAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
