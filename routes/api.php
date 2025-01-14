<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ImportacaoController;

Route::get('/', function () {
    return view('welcome');
});


<<<<<<< HEAD
Route::post('/processar-arquivo', [ImportacaoController::class, 'processarArquivo']);

// Route::post('importar/{tabela}', [ImportacaoController::class, 'processarImportacaoGenerica']);
=======
// Route::post('/processar-arquivo', [ImportacaoController::class, 'processarArquivo']);
Route::post('importar/{tabela}', [ImportacaoController::class, 'processarImportacaoGenerica']);
>>>>>>> 110b2c0dc21ab416b533fed4fbf0340c508ac6e4
