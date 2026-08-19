<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class EnvValidator
{
    public static function validate(): void
    {
        $required = ['APP_ENV','APP_URL','APP_KEY','DB_HOST','DB_DATABASE','DB_USERNAME','DB_PASSWORD'];
        $missing = [];
        foreach ($required as $key) {
            if (trim((string)env($key, '')) === '') $missing[] = $key;
        }
        if ($missing) throw new RuntimeException('Configuração obrigatória ausente: ' . implode(', ', $missing));

        $appKey = (string)env('APP_KEY');
        if (!str_starts_with($appKey, 'base64:')) {
            throw new RuntimeException('APP_KEY inválida: use uma chave base64 de 32 bytes.');
        }
        $decoded = base64_decode(substr($appKey, 7), true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('APP_KEY inválida: tamanho criptográfico incorreto.');
        }

        if ((string)env('APP_ENV', 'production') === 'production') {
            if (filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL)) {
                throw new RuntimeException('APP_DEBUG deve permanecer false em produção.');
            }
            $url = (string)env('APP_URL');
            if (!str_starts_with($url, 'https://')) {
                throw new RuntimeException('APP_URL deve usar HTTPS em produção.');
            }
            if (!filter_var(env('COOKIE_SECURE', 'true'), FILTER_VALIDATE_BOOL)) {
                throw new RuntimeException('COOKIE_SECURE deve ser true em produção.');
            }
        }
    }
}
