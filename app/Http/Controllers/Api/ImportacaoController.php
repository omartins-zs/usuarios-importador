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
            $data = array_filter(array_map('str_getcsv', explode("\n", $fileContents)), fn($row) => count($row) > 0);

            Log::info('Dados lidos do arquivo:', ['data' => $data]);

            // Verifica se os dados têm ao menos uma linha válida
            if (empty($data)) {
                Log::warning('Arquivo está vazio ou não contém dados válidos.');
                return response()->json(['message' => 'O arquivo não contém dados válidos'], 400);
            }

            // Verifica se o arquivo tem as colunas necessárias
            if (count($data[0]) < 3) {
                Log::warning('Arquivo com estrutura inválida (menos de 3 colunas).');
                return response()->json(['message' => 'Arquivo com estrutura inválida'], 400);
            }

            // Validação da estrutura do CSV (por exemplo, 3 colunas)
            $usuarios = [];
            foreach ($data as $index => $row) {
                // Log para verificar cada linha
                Log::info("Processando linha $index", ['linha' => $row]);

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
                Log::info('Dados a serem inseridos:', ['usuarios' => $usuarios]);

                // Verifique se há algum problema com a inserção
                try {
                    DB::table('usuarios')->insert($usuarios);
                    Log::info('Arquivo processado e salvo no banco de dados');
                } catch (\Exception $e) {
                    Log::error('Erro ao inserir no banco de dados: ' . $e->getMessage());
                    return response()->json(['message' => 'Erro ao inserir os dados no banco'], 500);
                }
            } else {
                Log::warning('Nenhum dado válido foi encontrado para inserção.');
            }

            return response()->json(['message' => 'Arquivo processado com sucesso!']);
        } catch (\Exception $e) {
            Log::error('Erro no processamento do arquivo: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao processar o arquivo'], 500);
        }
    }
}
