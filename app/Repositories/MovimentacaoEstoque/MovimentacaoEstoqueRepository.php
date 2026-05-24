<?php

namespace App\Repositories\MovimentacaoEstoque;

use App\Models\MovimentacaoEstoque;

class MovimentacaoEstoqueRepository
{
    public function create(array $data): MovimentacaoEstoque
    {
        return MovimentacaoEstoque::create($data);
    }
}
