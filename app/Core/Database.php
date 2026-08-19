<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function boot(): void { self::pdo(); }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) return self::$pdo;

        $host=(string)env('DB_HOST'); $port=(int)env('DB_PORT',3306);
        $db=(string)env('DB_DATABASE'); $charset=(string)env('DB_CHARSET','utf8mb4');
        if ($host==='' || $db==='') throw new RuntimeException('Banco de dados não configurado.');

        $options=[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_STRINGIFY_FETCHES=>false,
        ];
        $sslCa=trim((string)env('DB_SSL_CA',''));
        if ($sslCa!=='' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $options[PDO::MYSQL_ATTR_SSL_CA]=$sslCa;
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=true;
            }
        }

        $dsn="mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        try {
            self::$pdo=new PDO($dsn,(string)env('DB_USERNAME'),(string)env('DB_PASSWORD'),$options);
            try { self::$pdo->exec("SET time_zone = '-03:00'"); } catch (PDOException) {}
        } catch (PDOException $e) {
            if (filter_var(env('APP_DEBUG','false'), FILTER_VALIDATE_BOOL)) throw $e;
            throw new RuntimeException('Não foi possível conectar ao banco de dados.');
        }
        return self::$pdo;
    }
}
