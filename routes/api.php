<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('', function () {
        return response()->json(['api_name' => 'empresa-back-end', 'api_version' => '1.0.0']);
    });
    
    Route::prefix('clientes')->group(function () {require __DIR__ . '/routerFiles/clientesRouter.php';   });// Rota de Contas Bancarias
    Route::prefix('licitacoes')->group(function () {require __DIR__ . '/routerFiles/licitacoesRouter.php';   });// Rota de Contas Bancarias
    Route::prefix('analise-edital')->group(function () {require __DIR__ . '/routerFiles/analiseEditalRouter.php';   });// Rota de Contas Bancarias
    Route::prefix('orgao')->group(function () {require __DIR__ . '/routerFiles/orgaoRouter.php';   });// Rota de Contas Bancarias
    Route::prefix('pncp')->group(function () {require __DIR__ . '/routerFiles/pncpRouter.php';   });// Rota de Contas Bancarias
    Route::prefix('tipo-produto')->group(function () {require __DIR__ . '/routerFiles/tipoProdutoRouter.php';});// Rota de Marcas
    Route::prefix('subtipo-produto')->group(function () {require __DIR__ . '/routerFiles/subtipoProdutoRouter.php';});// Rota de Marcas
    Route::prefix('cores')->group(function () {require __DIR__ . '/routerFiles/coresRouter.php';});// Rota de Cores
    Route::prefix('marcas')->group(function () {require __DIR__ . '/routerFiles/marcasRouter.php';});// Rota de Marcas
// routes/api.php

// Route::post('/pncp/edital', [PncpController::class, 'buscarEdital']);

    // Route::prefix('linhas-produtos')->group(function () {require __DIR__ . '/routerFiles/linhaProdutosRouter.php';});// Rota de Linhas de Produtos
    // Route::prefix('produtos')->group(function () {require __DIR__ . '/routerFiles/produtosRouter.php';});// Rota de Produtos
    // Route::prefix('grades-produtos')->group(function () {require __DIR__ . '/routerFiles/gradeProdutosRouter.php';});// Rota de Grades de Produtos
    
    // Route::prefix('despesas')->group(function () {require __DIR__ . '/routerFiles/despesaRouter.php';});// Rota de despesas
    // Route::prefix('contas-pagar')->group(function () {require __DIR__ . '/routerFiles/contasPagarRouter.php';   });// Rota de contas-pagar
    // Route::prefix('lancamentos')->group(function () {require __DIR__ . '/routerFiles/lancamentosRouter.php';   });// Rota de contas-pagar
    // Route::prefix('receitas')->group(function () {require __DIR__ . '/routerFiles/receitasRouter.php';   });// Rota de Receitas
    // Route::prefix('contas-bancarias')->group(function () {require __DIR__ . '/routerFiles/contasBancariasRouter.php';   });// Rota de Contas Bancarias

});
