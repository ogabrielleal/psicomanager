<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Crypto
{
    private static function key(): string
    {
        $raw=(string)env('APP_KEY','');
        if ($raw==='') throw new RuntimeException('APP_KEY não configurada.');
        if (str_starts_with($raw,'base64:')) {
            $decoded=base64_decode(substr($raw,7),true);
            if ($decoded!==false && strlen($decoded)>=32) return substr($decoded,0,32);
        }
        return hash('sha256',$raw,true);
    }

    public static function encrypt(array|string|null $value): ?string
    {
        if ($value===null) return null;
        $plaintext=is_array($value)
            ? json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)
            : $value;
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt($plaintext,'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag);
        if ($cipher===false) throw new RuntimeException('Falha ao criptografar dado clínico.');
        return base64_encode($iv.$tag.$cipher);
    }

    public static function decrypt(?string $encoded, bool $json=false): mixed
    {
        if ($encoded===null || $encoded==='') return $json?[]:'';
        $raw=base64_decode($encoded,true);
        if ($raw===false || strlen($raw)<29) return $json?[]:'';
        $iv=substr($raw,0,12); $tag=substr($raw,12,16); $cipher=substr($raw,28);
        $plain=openssl_decrypt($cipher,'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag);
        if ($plain===false) return $json?[]:'';
        return $json ? (json_decode($plain,true) ?: []) : $plain;
    }
}
