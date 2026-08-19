<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32));
        return (string)$_SESSION['_csrf'];
    }
    public static function verify(string $token): void
    {
        if ($token==='' || !hash_equals(self::token(),$token)) {
            http_response_code(419);
            exit('Sessão do formulário expirou. Atualize a página e tente novamente.');
        }
    }
}
