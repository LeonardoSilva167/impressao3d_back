<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemCadastrarRequest;
use App\Http\Requests\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemEditarRequest;
use App\Services\ProjetoImpressaoParteItem\ProjetoImpressaoParteItemService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class ProjetoImpressaoParteItemController extends Controller
{
    private ProjetoImpressaoParteItemService $_service;

    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new ProjetoImpressaoParteItemService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsProjetoImpressaoParteItem()
    {
        try {
            $result = $this->_service->handleLookupsProjetoImpressaoParteItem();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressaoParteItem(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getProjetoImpressaoParteItemPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressaoParteItemId(string $id)
    {
        try {
            $result = $this->_service->getProjetoImpressaoParteItemId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createProjetoImpressaoParteItem(ProjetoImpressaoParteItemCadastrarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleAddProjetoImpressaoParteItem($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editProjetoImpressaoParteItem(ProjetoImpressaoParteItemEditarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleEditProjetoImpressaoParteItem($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteProjetoImpressaoParteItem(string $id)
    {
        try {
            $result = $this->_service->handleDeleteProjetoImpressaoParteItem($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressaoParteItemAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getProjetoImpressaoParteItemAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
