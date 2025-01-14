<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportacaoController extends Controller
{
    public function processarImportacao(Request $request, $tabela)
    {
        Log::info('Requisição recebida para importação na tabela: ' . $tabela);

        // Validação do arquivo
        $request->validate([
            'arquivo' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Max 10MB
        ]);

        $file = $request->file('arquivo');
        Log::info('Arquivo recebido', ['nome_original' => $file->getClientOriginalName()]);

        try {
            // Processar o CSV
            $data = array_map('str_getcsv', file($file->path(), FILE_IGNORE_NEW_LINES));

            // Primeira linha é o cabeçalho com os nomes das colunas
            $colunas = $data[0];
            $dados = [];

            // Processar as linhas seguintes (dados)
            foreach (array_slice($data, 1) as $index => $row) {
                if (count($row) < count($colunas)) {
                    Log::warning("Linha $index com número insuficiente de colunas.");
                    continue; // Ignorar linhas inválidas
                }

                // Criar um array associativo usando o cabeçalho como chave
                $linha = array_combine($colunas, $row);

                // Adicionar a data de criação e atualização
                $linha['created_at'] = now();
                $linha['updated_at'] = now();

                // Adicionar os dados para inserção
                $dados[] = $linha;
            }

            // Inserir em massa na tabela
            if (!empty($dados)) {
                DB::table($tabela)->insert($dados);
                Log::info("Dados inseridos na tabela $tabela com sucesso.");
            }

            return response()->json(['message' => 'Arquivo processado com sucesso!']);
        } catch (\Exception $e) {
            Log::error('Erro no processamento: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao processar o arquivo'], 500);
        }
    }
}
