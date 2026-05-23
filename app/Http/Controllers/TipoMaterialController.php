<?php

namespace App\Http\Controllers;

use App\Services\RequestDataService;
use App\Services\TipoMaterial\TipoMaterialService;
use Exception;
use Illuminate\Http\Request;

class TipoMaterialController extends Controller
{
    /**
     * @var TipoMaterialService $_service
     */
    private TipoMaterialService $_service;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new TipoMaterialService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsTipoMaterial()
    {
        try {
            $result = $this->_service->handleLookupsTipoMaterial();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarTipoMaterial(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getTipoMaterialPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarTipoMaterialId(string $id)
    {
        try {
            $result = $this->_service->getTipoMaterialId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createTipoMaterial(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result          = $this->_service->handleAddTipoMaterial($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editTipoMaterial(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result          = $this->_service->handleEditTipoMaterial($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteTipoMaterial(string $id)
    {
        try {
            $result = $this->_service->handleDeleteTipoMaterial($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarTipoMaterialAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getTipoMaterialAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
