<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

$user = require_permission('finance.manage');
\App\Core\Tenant::enforceActive((int)$user['tenant_id']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('finance/index.php');
}

verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
if (!in_array($status, ['pending', 'paid', 'cancelled', 'courtesy'], true)) {
    redirect('finance/index.php');
}

$lookup = db()->prepare(
    "SELECT f.id,f.professional_id,f.patient_id,p.primary_professional_id
       FROM financial_transactions f
       LEFT JOIN patients p ON p.id=f.patient_id AND p.tenant_id=f.tenant_id
      WHERE f.id=:id AND f.tenant_id=:t
      LIMIT 1"
);
$lookup->execute(['id' => $id, 't' => $user['tenant_id']]);
$transaction = $lookup->fetch();

if (!$transaction) {
    http_response_code(404);
    exit('Lançamento não localizado.');
}

if (in_array($user['role_slug'], ['psychologist', 'supervised'], true)) {
    $ownsTransaction = (int)($transaction['professional_id'] ?? 0) === (int)$user['id'];
    $ownsPatient = (int)($transaction['primary_professional_id'] ?? 0) === (int)$user['id'];
    if (!$ownsTransaction && !$ownsPatient) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}

$sql = "UPDATE financial_transactions
           SET status=:status,
               paid_at=CASE WHEN :is_paid=1 THEN COALESCE(paid_at,NOW()) ELSE NULL END,
               updated_at=NOW()
         WHERE id=:id AND tenant_id=:tenant";
$st = db()->prepare($sql);
$st->execute(['status' => $status, 'is_paid' => $status === 'paid' ? 1 : 0, 'id' => $id, 'tenant' => $user['tenant_id']]);

audit('finance.transaction_status', 'financial_transaction', $id, ['status' => $status]);
flash('success', 'Status financeiro atualizado.');
redirect('finance/index.php');
