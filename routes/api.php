<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ImportacaoController;

Route::get('/', function () {
    return view('welcome');
});


// Route::post('/processar-arquivo', [ImportacaoController::class, 'processarArquivo']);
Route::post('importar/{tabela}', [ImportacaoController::class, 'processarImportacaoGenerica']);
