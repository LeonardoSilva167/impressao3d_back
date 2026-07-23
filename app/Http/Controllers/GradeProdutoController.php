<?php

namespace App\Http\Controllers;

use App\Exceptions\PartesPendentesMontagemException;
use App\Http\Requests\GradeProduto\GradeProdutoCadastrarRequest;
use App\Http\Requests\GradeProduto\GradeProdutoEditarRequest;
use App\Http\Requests\GradeProduto\GradeProdutoGerarGradeRequest;
use App\Http\Requests\GradeProduto\GradeProdutoPreviewProdutosRequest;
use App\Services\GradeProduto\GradeProdutoService;
use App\Services\RequestDataService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeProdutoController extends Controller
{
    private GradeProdutoService $_service;

    protected $_requestService;

    public function __construct()
    {
        $this->_service        = new GradeProdutoService();
        $this->_requestService = new RequestDataService();
    }

    public function listarLookupsGradeProduto()
    {
        try {
            $result = $this->_service->handleLookupsGradeProduto();
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function listarGradeProduto(Request $request)
    {
        try {
            $objectAtributes = $this->_requestService->getAllParametersForQuery($request);
            $result          = $this->_service->getGradeProdutoPaginate($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function listarGradeProdutoId(string $id)
    {
        try {
            $result = $this->_service->getGradeProdutoId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function listarGradeProdutoGradeId(string $id)
    {
        try {
            $result = $this->_service->getGradeProdutoGradeId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function carregarComposicaoGradeProduto(Request $request)
    {
        try {
            $idProdutoBase = (int) ($request->query('id_produto_base') ?? $request->query('id_produto') ?? 0);

            if ($idProdutoBase <= 0) {
                throw new Exception('O produto base é obrigatório.', 422);
            }

            $result = $this->_service->carregarComposicaoPorProdutoBase($idProdutoBase);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function createGradeProduto(GradeProdutoCadastrarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleAddGradeProduto($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function editGradeProduto(GradeProdutoEditarRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handleEditGradeProduto($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function deleteGradeProduto(string $id)
    {
        try {
            $result = $this->_service->handleDeleteGradeProduto($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function previewProdutosGradeProduto(GradeProdutoPreviewProdutosRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();
            $result          = $this->_service->handlePreviewProdutos($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function gerarGradeProduto(GradeProdutoGerarGradeRequest $request)
    {
        try {
            $objectAtributes = (object) $request->validated();

            if (!empty($objectAtributes->id)) {
                $result = $this->_service->handleGerarProdutos((int) $objectAtributes->id, true);
            } else {
                $result = $this->_service->handleGerarGrade($objectAtributes);
            }

            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function gerarProdutosGradeProduto(string $id)
    {
        try {
            $result = $this->_service->handleGerarProdutos((int) $id, true);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    public function listarGradeProdutoAsync(Request $request)
    {
        try {
            $params = (object) $request->all();
            $result = $this->_service->getGradeProdutoAsync($params);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            return $this->respondError($ex);
        }
    }

    private function respondError(Exception $ex): JsonResponse
    {
        if ($ex instanceof PartesPendentesMontagemException) {
            return response()->json($ex->toArray(), 422);
        }

        $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
        $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;

        return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
    }
}
