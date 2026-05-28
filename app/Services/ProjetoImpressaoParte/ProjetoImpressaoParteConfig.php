<?php

namespace App\Services\ProjetoImpressaoParte;

class ProjetoImpressaoParteConfig
{
    public const ALTURAS_POR_BICO = [
        '0.2' => ['0.06', '0.08', '0.10', '0.12', '0.14'],
        '0.4' => ['0.08', '0.12', '0.16', '0.20', '0.24', '0.28'],
        '0.6' => ['0.20', '0.24', '0.28', '0.32', '0.36'],
        '0.8' => ['0.28', '0.32', '0.40', '0.48', '0.56'],
    ];

    public const BICOS_PADRAO = ['0.2', '0.4', '0.6', '0.8'];

    public const TIPOS_SUPORTE = ['ARVORE_PADRAO', 'ARVORE_FORTE'];

    public const TEMPERATURA_BICO_PADRAO = 210;

    public const TEMPERATURA_MESA_PADRAO = 75;
}
