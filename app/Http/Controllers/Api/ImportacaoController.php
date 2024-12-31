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
    public function processarArquivo(Request $request)
    {
        Log::info('Requisição recebida no Importador', ['headers' => $request->headers->all()]);

        // Validação do arquivo
        $request->validate([
            'arquivo' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Max 10MB
        ]);

        $file = $request->file('arquivo');
        Log::info('Arquivo recebido no Importador', ['nome_original' => $file->getClientOriginalName()]);

        // Processamento do CSV
        try {
            // Lê o conteúdo do arquivo e converte para UTF-8 caso necessário
            $fileContents = file_get_contents($file->path());
            $fileContents = mb_convert_encoding($fileContents, 'UTF-8', 'ISO-8859-1'); // Certifica-se de que o arquivo esteja em UTF-8
            $data = array_map('str_getcsv', explode("\n", $fileContents));

            // Validação da estrutura do CSV (por exemplo, 3 colunas)
            $usuarios = [];
            foreach ($data as $index => $row) {
                if (count($row) < 3) {
                    Log::warning("Linha $index tem menos de 3 colunas e será ignorada.");
                    continue; // Ignora linhas com estrutura inválida
                }

                // Tratar e validar dados antes da inserção
                $status = in_array($row[2], ['ativo', 'inativo']) ? $row[2] : 'ativo';

                // Adiciona os dados para inserção em massa
                $usuarios[] = [
                    'nome' => $row[0],
                    'email' => $row[1],
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Inserir os dados no banco de dados
            if (isset($usuarios) && count($usuarios) > 0) {
                DB::table('usuarios')->insert($usuarios);
                Log::info('Arquivo processado e salvo no banco de dados');
            }

            return response()->json(['message' => 'Arquivo processado com sucesso!']);
        } catch (\Exception $e) {
            Log::error('Erro no processamento do arquivo: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao processar o arquivo'], 500);
        }
    }
}
