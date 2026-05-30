<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProdutoComposicao\ProdutoComposicaoCadastrarRequest;
use App\Http\Requests\ProdutoComposicao\ProdutoComposicaoEditarRequest;
use App\Services\ProdutoComposicao\ProdutoComposicaoService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\Request;

class ProdutoComposicaoController extends Controller
{
    /**
     * @var ProdutoComposicaoService $_service
     */
    private ProdutoComposicaoService $_service;

    /**
     * @var RequestDataService $_requestService
     */
    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new ProdutoComposicaoService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsProdutoComposicao()
    {
        try {
            $result = $this->_service->handleLookupsProdutoComposicao();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProdutoComposicao(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getProdutoComposicaoPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProdutoComposicaoId(string $id)
    {
        try {
            $result = $this->_service->getProdutoComposicaoId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function carregarProdutoComposicao(Request $request)
    {
        try {
            $idProduto = (int) ($request->query('id_produto_base') ?? $request->query('id_produto'));
            $idProjeto = (int) $request->query('id_projeto_impressao');

            if ($idProduto <= 0) {
                throw new Exception('O produto base é obrigatório.', 422);
            }

            if ($idProjeto <= 0) {
                throw new Exception('O projeto de impressão é obrigatório.', 422);
            }

            $result = $this->_service->carregarDadosComposicao($idProduto, $idProjeto);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function createProdutoComposicao(ProdutoComposicaoCadastrarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleAddProdutoComposicao($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editProdutoComposicao(ProdutoComposicaoEditarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleEditProdutoComposicao($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function deleteProdutoComposicao(string $id)
    {
        try {
            $result = $this->_service->handleDeleteProdutoComposicao($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function listarProdutoComposicaoAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getProdutoComposicaoAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
