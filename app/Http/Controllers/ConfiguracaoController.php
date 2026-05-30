<?php

namespace App\Http\Controllers;

use App\Services\Configuracao\ConfiguracaoService;
use Exception;
use Illuminate\Http\Request;

class ConfiguracaoController extends Controller
{
    private ConfiguracaoService $_service;

    public function __construct()
    {
        $this->_service = new ConfiguracaoService();
    }

    public function listarConfiguracaoId(string $id)
    {
        try {
            $result = $this->_service->getConfiguracaoId($id);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }

    public function editConfiguracao(Request $request)
    {
        try {
            $objectAtributes = (object) $request->all();
            $result          = $this->_service->handleEditConfiguracao($objectAtributes);
            return response()->json($result, 200);
        } catch (Exception $ex) {
            $statusCode = is_numeric($ex->getCode()) ? (int) $ex->getCode() : 500;
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 500;
            return response()->json(['error' => true, 'message' => $ex->getMessage()], $statusCode);
        }
    }
}
