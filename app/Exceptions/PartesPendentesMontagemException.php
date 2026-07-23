<?php

namespace App\Exceptions;

use Exception;

class PartesPendentesMontagemException extends Exception
{
    /** @var array<int, array{id_projeto_impressao_parte: int, nome_parte: string}> */
    private array $partesPendentes;

    /**
     * @param  array<int, array{id_projeto_impressao_parte: int, nome_parte: string}>  $partesPendentes
     */
    public function __construct(array $partesPendentes)
    {
        $this->partesPendentes = array_values($partesPendentes);

        parent::__construct(
            'Não é possível montar: existem partes sem configuração completa.',
            422
        );
    }

    /**
     * @return array<int, array{id_projeto_impressao_parte: int, nome_parte: string}>
     */
    public function getPartesPendentes(): array
    {
        return $this->partesPendentes;
    }

    public function toArray(): array
    {
        return [
            'error'            => true,
            'message'          => $this->getMessage(),
            'partes_pendentes' => $this->partesPendentes,
        ];
    }
}
