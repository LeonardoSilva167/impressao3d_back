<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjetoImpressaoParte\ProjetoImpressaoParteCadastrarRequest;
use App\Http\Requests\ProjetoImpressaoParte\ProjetoImpressaoParteEditarRequest;
use App\Services\ProjetoImpressaoParte\ProjetoImpressaoParteService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class ProjetoImpressaoParteController extends Controller
{
    /**
     * @var ProjetoImpressaoParteService $_service
     */
    private ProjetoImpressaoParteService $_service;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new ProjetoImpressaoParteService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsProjetoImpressaoParte()
    {
        try {
            $result = $this->_service->handleLookupsProjetoImpressaoParte();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressaoParte(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getProjetoImpressaoPartePaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressaoParteId(string $id)
    {
        try {
            $result = $this->_service->getProjetoImpressaoParteId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createProjetoImpressaoParte(ProjetoImpressaoParteCadastrarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleAddProjetoImpressaoParte($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editProjetoImpressaoParte(ProjetoImpressaoParteEditarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleEditProjetoImpressaoParte($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteProjetoImpressaoParte(string $id)
    {
        try {
            $result = $this->_service->handleDeleteProjetoImpressaoParte($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProjetoImpressaoParteAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getProjetoImpressaoParteAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
