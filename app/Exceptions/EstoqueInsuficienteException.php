<?php

namespace App\Exceptions;

use Exception;

class EstoqueInsuficienteException extends Exception
{
    public function __construct()
    {
        parent::__construct('Estoque insuficiente para finalizar esta quantidade.', 422);
    }
}
