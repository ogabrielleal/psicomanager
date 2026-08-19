<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$limit=\App\Core\RateLimiter::hit('client-error:'.client_ip(),60,60);
if(!$limit['allowed']){http_response_code(429);exit;}
$raw = file_get_contents('php://input');
if (strlen((string)$raw) > 8192) { http_response_code(413); exit; }
$data = json_decode((string)$raw, true);
if (!is_array($data)) { http_response_code(400); exit; }
\App\Core\ErrorHandler::client($data);
http_response_code(204);
