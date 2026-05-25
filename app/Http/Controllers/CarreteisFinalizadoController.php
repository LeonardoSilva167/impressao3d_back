<?php

namespace App\Http\Controllers;

use App\Exceptions\EstoqueInsuficienteException;
use App\Http\Requests\CarreteisFinalizado\CarreteisFinalizadoCadastrarRequest;
use App\Http\Requests\CarreteisFinalizado\CarreteisFinalizadoEditarRequest;
use App\Http\Requests\CarreteisFinalizado\CarreteisFinalizadoLotesConsumoRequest;
use App\Services\CarreteisFinalizado\CarreteisFinalizadoService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class CarreteisFinalizadoController extends Controller
{
    /**
     * @var CarreteisFinalizadoService $_service
     */
    private CarreteisFinalizadoService $_service;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new CarreteisFinalizadoService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsCarreteisFinalizado()
    {
        try {
            $result = $this->_service->handleLookupsCarreteisFinalizado();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarCarreteisFinalizados(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getCarreteisFinalizadosPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarCarreteisFinalizadoId(string $id)
    {
        try {
            $result = $this->_service->getCarreteisFinalizadoId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createCarreteisFinalizado(CarreteisFinalizadoCadastrarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleAddCarreteisFinalizado($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editCarreteisFinalizado(CarreteisFinalizadoEditarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleEditCarreteisFinalizado($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteCarreteisFinalizado(string $id)
    {
        try {
            $result = $this->_service->handleDeleteCarreteisFinalizado($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarCarreteisFinalizadoAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getCarreteisFinalizadoAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarLotesConsumo(CarreteisFinalizadoLotesConsumoRequest $request)
    {
        try {
            $request->validated();

            $idItem     = (int) $request->query('id_item');
            $gramatura  = (int) $request->query('gramatura');
            $quantidade = (int) $request->query('quantidade');

            $result = $this->_service->getLotesConsumo($idItem, $quantidade, $gramatura);

            return response()->json($result, 200);
        } catch (EstoqueInsuficienteException $ex) {
            return response()->json([
                'error'                => true,
                'estoque_insuficiente' => true,
                'message'              => $ex->getMessage(),
            ], 422);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
