<?php
declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

$user = require_permission('patients.view');
\App\Core\Tenant::enforceActive((int)$user['tenant_id']);
$q = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT p.*,u.name professional_name
          FROM patients p
          JOIN users u ON u.id=p.primary_professional_id AND u.tenant_id=p.tenant_id
         WHERE p.tenant_id=:t";
$params = ['t' => $user['tenant_id']];

if ($user['role_slug'] === 'supervised') {
    $sql .= ' AND p.primary_professional_id=:u';
    $params['u'] = $user['id'];
} elseif ($user['role_slug'] === 'psychologist') {
    $sql .= " AND (
        p.primary_professional_id=:u
        OR EXISTS (
            SELECT 1 FROM users su
             WHERE su.id=p.primary_professional_id
               AND su.tenant_id=p.tenant_id
               AND su.supervisor_id=:u2
               AND su.active=1
        )
    )";
    $params['u'] = $user['id'];
    $params['u2'] = $user['id'];
}

if ($q !== '') {
    $sql .= ' AND (p.name LIKE :q_name OR p.preferred_name LIKE :q_preferred OR p.code LIKE :q_code OR p.cpf LIKE :q_cpf)';
    $search = '%'.$q.'%';
    $params['q_name'] = $search;
    $params['q_preferred'] = $search;
    $params['q_code'] = $search;
    $params['q_cpf'] = $search;
}
$sql .= ' ORDER BY p.name LIMIT 150';

$st = db()->prepare($sql);
$st->execute($params);
$patients = $st->fetchAll();

layout('Pacientes', function () use ($patients, $q, $user) { ?>
<div class="page-head">
    <div><span class="eyebrow">Relacionamento clínico</span><h1>Pacientes</h1><p>Dados cadastrais separados do conteúdo clínico criptografado.</p></div>
    <?php if (\App\Core\Rbac::allows((int)$user['id'], 'patients.manage')): ?><a class="button" href="<?=e(url('patients/new.php'))?>">+ Novo paciente</a><?php endif ?>
</div>
<div class="card">
    <form method="get" class="split-actions" style="margin-bottom:16px"><input name="q" value="<?=e($q)?>" placeholder="Nome, código ou CPF"><button class="button secondary small">Buscar</button></form>
    <div class="table-wrap"><table class="table"><thead><tr><th>Código</th><th>Paciente</th><th>Profissional</th><th>Abordagem</th><th>Status</th><th></th></tr></thead><tbody>
    <?php if (!$patients): ?><tr><td colspan="6" class="muted">Nenhum paciente encontrado.</td></tr><?php endif ?>
    <?php foreach ($patients as $p): ?><tr><td><?=e($p['code'])?></td><td data-private><strong><?=e($p['preferred_name'] ?: $p['name'])?></strong></td><td><?=e($p['professional_name'])?></td><td><?=e($p['approach'] ?: '—')?></td><td><span class="status <?=e($p['status'])?>"><?=e($p['status'])?></span></td><td><a href="<?=e(url('patients/view.php?id='.$p['id']))?>">Abrir</a></td></tr><?php endforeach ?>
    </tbody></table></div>
</div>
<?php }, ['active' => 'patients']);
