<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

$user = require_permission('finance.view');
\App\Core\Tenant::enforceActive((int)$user['tenant_id']);
$id = (int)($_GET['id'] ?? 0);

$st = db()->prepare(
    "SELECT f.*,
            p.name patient_name,
            p.cpf patient_cpf,
            p.primary_professional_id,
            u.name professional_name,
            u.professional_crp,
            t.name tenant_name,
            t.document tenant_document
       FROM financial_transactions f
       LEFT JOIN patients p ON p.id=f.patient_id AND p.tenant_id=f.tenant_id
       LEFT JOIN users u ON u.id=COALESCE(f.professional_id,f.created_by) AND u.tenant_id=f.tenant_id
       JOIN tenants t ON t.id=f.tenant_id
      WHERE f.id=:id
        AND f.tenant_id=:t
        AND f.type='income'
        AND f.status='paid'
      LIMIT 1"
);
$st->execute(['id' => $id, 't' => $user['tenant_id']]);
$f = $st->fetch();

if ($f && in_array($user['role_slug'], ['psychologist', 'supervised'], true)) {
    $ownsTransaction = (int)($f['professional_id'] ?? 0) === (int)$user['id'];
    $ownsPatient = (int)($f['primary_professional_id'] ?? 0) === (int)$user['id'];
    if (!$ownsTransaction && !$ownsPatient) {
        $f = false;
    }
}

if (!$f) {
    http_response_code(404);
    exit('Receita paga não localizada.');
}

$code = $f['receipt_code'] ?: ('REC-'.date('Ym').'-'.str_pad((string)$id, 6, '0', STR_PAD_LEFT));
if (!$f['receipt_code']) {
    db()->prepare("UPDATE financial_transactions SET receipt_code=:c WHERE id=:id AND tenant_id=:t")
        ->execute(['c' => $code, 'id' => $id, 't' => $user['tenant_id']]);
}

audit('finance.receipt_pdf', 'financial_transaction', $id, []);
$lines = [
    'RECIBO '.$code,
    '',
    'Recebemos de: '.($f['patient_name'] ?: 'Paciente/cliente'),
    'CPF do pagador: '.($f['payer_document'] ?: $f['patient_cpf'] ?: 'não informado'),
    'Valor: R$ '.number_format((float)$f['amount'], 2, ',', '.'),
    'Referente a: '.$f['description'],
    'Data do pagamento: '.date('d/m/Y', strtotime($f['paid_at'] ?: $f['created_at'])),
    '',
    'Emitente: '.$f['tenant_name'],
    trim('Profissional: '.$f['professional_name'].' '.($f['professional_crp'] ?: '')),
    '',
    'Documento gerado pelo PsicoManager AI.',
];

\App\Core\SimplePdf::output($lines, 'recibo-'.$code.'.pdf');
