<?php
$flashes=pull_flashes();$user=current_user();
$nav=[
 ['dashboard','Painel','dashboard/index.php','dashboard.view'],
 ['agenda','Agenda','agenda/index.php','agenda.view'],
 ['patients','Pacientes','patients/index.php','patients.view'],
 ['clinical','Prontuário','clinical/index.php','clinical.view'],
 ['documents','Documentos','documents/index.php','documents.view'],
 ['ai','Copiloto IA','ai/index.php','ai.use'],
 ['finance','Financeiro','finance/index.php','finance.view'],
 ['knowledge','Base científica','knowledge/index.php','knowledge.manage'],
 ['team','Equipe','team/index.php','team.manage'],
 ['security','Segurança','security/index.php','security.view'],
 ['billing','Plano','billing/index.php','settings.manage'],
];
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="request-id" content="<?=e(request_id())?>">
<title><?= e($pageTitle) ?> · <?= e(env('APP_NAME','PsicoManager AI')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
</head>
<body class="<?= $public?'public-body':'app-body' ?>">
<?php if($public): ?>
<?= $body ?>
<?php elseif($portal): ?>
<header class="portal-top"><a class="brand" href="<?=e(url('portal/index.php'))?>"><span class="brand-mark">P</span><span>PsicoManager <b>AI</b></span></a><a href="<?=e(url('portal/logout.php'))?>" class="button ghost small">Sair</a></header>
<main class="portal-shell"><?= $body ?></main>
<?php else: ?>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
<a class="brand app-brand" href="<?=e(url('dashboard/index.php'))?>"><span class="brand-mark">P</span><span>PsicoManager <b>AI</b></span></a>
<nav class="nav">
<?php foreach($nav as [$key,$label,$href,$permission]): ?>
<?php if($user&&\App\Core\Rbac::allows((int)$user['id'],$permission)): ?>
<a class="nav-item <?=$active===$key?'active':''?>" href="<?=e(url($href))?>"><span class="nav-dot"></span><?=e($label)?></a>
<?php endif ?>
<?php endforeach ?>
</nav>
<div class="sidebar-footer"><div class="tenant-name"><?=e($user['tenant_name']??'')?></div><a href="<?=e(url('settings/index.php'))?>">Configurações</a><a href="<?=e(url('logout.php'))?>">Sair</a></div>
</aside>
<section class="main">
<header class="topbar">
<button class="icon-button mobile-only" data-sidebar aria-label="Abrir menu">☰</button>
<div class="searchbox"><span>⌕</span><input id="global-search" placeholder="Buscar pacientes e telas" aria-label="Busca global"><kbd>Ctrl K</kbd></div>
<div class="top-actions"><button class="icon-button" data-privacy title="Modo discreto" aria-label="Alternar modo discreto">◉</button><div class="user-chip"><span class="avatar"><?=e(mb_strtoupper(mb_substr($user['name']??'U',0,1)))?></span><div><strong><?=e($user['name']??'')?></strong><small><?=e($user['role_name']??'')?></small></div></div></div>
</header>
<?php foreach($flashes as $f):?><div class="flash <?=e($f['type'])?>"><?=e($f['message'])?></div><?php endforeach?>
<main class="content"><?= $body ?></main>
</section>
</div>
<div class="privacy-toast" id="privacy-toast">Modo discreto ativo: nomes e valores estão ocultos.</div>
<?php endif ?>
<script src="<?=e(url('assets/js/app.js'))?>"></script>
</body></html>