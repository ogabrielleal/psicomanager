<?php
declare(strict_types=1);

function env(string $key, mixed $default = null): mixed {
    return \App\Core\Env::get($key, $default);
}
function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function is_installed(): bool {
    return is_file(dirname(__DIR__) . '/storage/installed.lock') && is_file(dirname(__DIR__) . '/.env');
}
function app_url(): string {
    return rtrim((string) env('APP_URL', ''), '/');
}
function url(string $path = ''): string {
    $base = app_url();
    if ($base === '') return '/' . ltrim($path, '/');
    return $base . '/' . ltrim($path, '/');
}
function redirect(string $path): never {
    $target = preg_match('~^https?://~', $path) ? $path : url($path);
    header('Location: ' . $target);
    exit;
}
function csrf_token(): string { return \App\Core\Csrf::token(); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void { \App\Core\Csrf::verify($_POST['_csrf'] ?? ''); }
function flash(string $type, string $message): void { $_SESSION['_flash'][] = ['type'=>$type,'message'=>$message]; }
function pull_flashes(): array {
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($items) ? $items : [];
}
function current_user(): ?array { return \App\Core\Auth::user(); }
function tenant_id(): int { $u=current_user(); return (int)($u['tenant_id'] ?? 0); }
function require_auth(): array { return \App\Core\Auth::requireUser(); }
function require_permission(string $permission): array {
    $u = \App\Core\Auth::requireUser();
    if (!\App\Core\Rbac::allows((int)$u['id'], $permission)) {
        http_response_code(403);
        require APP_ROOT . '/app/views/403.php';
        exit;
    }
    $prefix=explode('.', $permission, 2)[0];
    $featureMap=['agenda'=>'agenda','patients'=>'patients','clinical'=>'clinical','documents'=>'documents','ai'=>'ai','finance'=>'finance','team'=>'team'];
    if(isset($featureMap[$prefix])&&!\App\Core\FeatureFlags::enabled($featureMap[$prefix],(int)$u['tenant_id'])){
        http_response_code(403);
        $feature=$featureMap[$prefix];
        require APP_ROOT . '/app/views/feature_disabled.php';
        exit;
    }
    return $u;
}
function db(): PDO { return \App\Core\Database::pdo(); }
function audit(string $action, string $entityType, ?int $entityId = null, array $meta = []): void {
    \App\Core\Audit::record($action, $entityType, $entityId, $meta);
}
function client_ip(): string {
    $remote=substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),0,45);
    if(filter_var(env('TRUST_PROXY_HEADERS','false'),FILTER_VALIDATE_BOOL)){
        $candidate=trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if(str_contains($candidate,','))$candidate=trim(explode(',',$candidate)[0]);
        if(filter_var($candidate,FILTER_VALIDATE_IP))return substr($candidate,0,45);
    }
    return $remote;
}
function request_id(): string { return \App\Core\RequestContext::id(); }
function feature_enabled(string $feature, ?int $tenantId = null): bool { $tenantId ??= tenant_id(); return $tenantId > 0 && \App\Core\FeatureFlags::enabled($feature, $tenantId); }
function layout(string $title, callable $content, array $options = []): void {
    $pageTitle = $title;
    $active = $options['active'] ?? '';
    $public = (bool)($options['public'] ?? false);
    $portal = (bool)($options['portal'] ?? false);
    ob_start();
    $content();
    $body = ob_get_clean();
    require APP_ROOT . '/app/views/layout.php';
}
