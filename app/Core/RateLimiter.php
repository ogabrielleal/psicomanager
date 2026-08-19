<?php
declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    public static function hit(string $key, int $maxAttempts, int $windowSeconds): array
    {
        $maxAttempts = max(1, $maxAttempts);
        $windowSeconds = max(1, $windowSeconds);
        $dir = APP_ROOT . '/storage/rate-limits';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $file = $dir . '/' . hash('sha256', $key) . '.json';
        $now = time();
        $fh = @fopen($file, 'c+');
        if (!$fh) return ['allowed'=>true,'remaining'=>$maxAttempts-1,'retry_after'=>0];
        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $data = json_decode($raw ?: '{}', true) ?: [];
        $start = (int)($data['start'] ?? $now);
        $count = (int)($data['count'] ?? 0);
        if ($now - $start >= $windowSeconds) { $start = $now; $count = 0; }
        $count++;
        $allowed = $count <= $maxAttempts;
        $retry = $allowed ? 0 : max(1, $windowSeconds - ($now - $start));
        ftruncate($fh, 0); rewind($fh);
        fwrite($fh, json_encode(['start'=>$start,'count'=>$count], JSON_UNESCAPED_SLASHES));
        fflush($fh); flock($fh, LOCK_UN); fclose($fh);
        @chmod($file, 0600);
        return ['allowed'=>$allowed,'remaining'=>max(0,$maxAttempts-$count),'retry_after'=>$retry];
    }

    public static function clear(string $key): void
    {
        $file = APP_ROOT . '/storage/rate-limits/' . hash('sha256', $key) . '.json';
        if (is_file($file)) @unlink($file);
    }
}
