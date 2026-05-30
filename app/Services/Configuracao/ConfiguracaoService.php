<?php

namespace App\Services\Configuracao;

use App\Repositories\Configuracao\ConfiguracaoRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class ConfiguracaoService
{
    private ConfiguracaoRepository $_repository;

    public function __construct()
    {
        $this->_repository = new ConfiguracaoRepository();
    }

    public function handleEditConfiguracao(object $atributes): object
    {
        try {
            DB::beginTransaction();

            $result                   = (object) [];
            $result->configuracao = $this->updateConfiguracao($atributes);

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateConfiguracao(object $atributes): object
    {
        $record = $this->_repository->findById($atributes->id ?? null);

        if (!$record) {
            throw new Exception('Configuração não encontrada', 404);
        }

        $dados = [];

        if (isset($atributes->custo_energia_kwh)) {
            $dados['custo_energia_kwh'] = $atributes->custo_energia_kwh;
        }

        if (isset($atributes->custo_desgaste_hora)) {
            $dados['custo_desgaste_hora'] = $atributes->custo_desgaste_hora;
        }

        if (isset($atributes->proximo_codigo_base)) {
            $dados['proximo_codigo_base'] = $atributes->proximo_codigo_base;
        }

        if ($dados === []) {
            throw new Exception('Nenhum campo informado para alteração', 422);
        }

        $saved = $this->_repository->update($record, $dados);

        if (!$saved) {
            throw new Exception('Não foi possível alterar configuração', 500);
        }

        return (object) [
            'data'    => [],
            'status'  => true,
            'message' => 'Configuração alterada com sucesso!',
        ];
    }

    public function getConfiguracaoId(int|string $id): array
    {
        $record = $this->_repository->findById($id);

        if (!$record) {
            throw new Exception('Configuração não encontrada', 404);
        }

        return [
            'id'                  => (int) $record->id,
            'proximo_codigo_base' => (int) $record->proximo_codigo_base,
            'custo_energia_kwh'   => round((float) $record->custo_energia_kwh, 4),
            'custo_desgaste_hora' => round((float) $record->custo_desgaste_hora, 4),
            'created_at'          => $record->created_at,
            'updated_at'          => $record->updated_at,
        ];
    }
}
