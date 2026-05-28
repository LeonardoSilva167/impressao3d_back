<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjetoImpressao\ProjetoImpressaoCadastrarRequest;
use App\Http\Requests\ProjetoImpressao\ProjetoImpressaoEditarRequest;
use App\Services\ProjetoImpressao\ProjetoImpressaoService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class ProjetoImpressaoController extends Controller
{
    /**
     * @var ProjetoImpressaoService $_service
     */
    private ProjetoImpressaoService $_service;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new ProjetoImpressaoService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsProjetoImpressao()
    {
        try {
            $result = $this->_service->handleLookupsProjetoImpressao();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressao(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getProjetoImpressaoPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressaoId(string $id)
    {
        try {
            $result = $this->_service->getProjetoImpressaoId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createProjetoImpressao(ProjetoImpressaoCadastrarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleAddProjetoImpressao($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editProjetoImpressao(ProjetoImpressaoEditarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleEditProjetoImpressao($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteProjetoImpressao(string $id)
    {
        try {
            $result = $this->_service->handleDeleteProjetoImpressao($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressaoAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getProjetoImpressaoAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
