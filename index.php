<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
$plans=[];
if(is_installed()){try{$plans=db()->query("SELECT * FROM plans WHERE active=1 ORDER BY price_monthly")->fetchAll();}catch(Throwable){}}
if(!$plans)$plans=[
 ['name'=>'Essencial','slug'=>'essencial','price_monthly'=>'49.00','features_json'=>json_encode(['Até 20 pacientes','Agenda e financeiro','Prontuário básico'])],
 ['name'=>'Profissional','slug'=>'profissional','price_monthly'=>'99.00','features_json'=>json_encode(['Pacientes ilimitados','Copiloto IA','Documentos CFP e RAG'])],
 ['name'=>'Clínicas / Equipes','slug'=>'clinicas','price_monthly'=>'199.00','features_json'=>json_encode(['Multiusuários','RBAC','Relatórios consolidados'])]
];
layout('Gestão clínica com inteligência responsável',function()use($plans){?>
<nav class="landing-nav">
<a class="brand" href="#inicio"><span class="brand-mark">P</span><span>PsicoManager <b>AI</b></span></a>
<nav><a href="#produto">Produto</a><a href="#ia">IA científica</a><a href="#seguranca">Segurança</a><a href="#planos">Planos</a></nav>
<div class="split-actions"><a class="button secondary" href="<?=e(url('login.php'))?>">Entrar</a><a class="button" href="<?=e(url('cadastro.php'))?>">Criar conta</a></div>
</nav>
<section class="hero" id="inicio">
<div><span class="eyebrow">Gestão clínica, sem ruído administrativo</span><h1>Mais tempo para <em>cuidar.</em> Menos tempo organizando sistemas.</h1>
<p>Agenda, prontuários, documentos, financeiro, portal do paciente e um copiloto de IA que trabalha com anonimização e fontes internas — em uma experiência desenhada para a rotina de psicólogos e clínicas.</p>
<div class="hero-actions"><a class="button" href="<?=e(url('cadastro.php'))?>">Começar agora</a><a class="button ghost" href="#produto">Conhecer a plataforma</a></div>
<div class="trust-row"><span>Dados clínicos segregados</span><span>Controle por perfil</span><span>MySQL remoto</span></div></div>
<div class="hero-visual"><img class="hero-photo" fetchpriority="high" decoding="async" src="https://images.unsplash.com/photo-1758273241086-f3585ef8c2f8?auto=format&fit=crop&fm=jpg&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&ixlib=rb-4.1.0&q=60&w=3000" alt="Profissional de psicologia em atendimento clínico">
<div class="floating-card one"><strong>6 atendimentos</strong><small>4 confirmados hoje</small></div><div class="floating-card two"><strong>Prontuário protegido</strong><small>Acesso por profissional responsável</small></div></div>
</section>
<div class="logo-strip">Uma plataforma única para prática clínica, organização da clínica e relacionamento com o paciente.</div>
<section class="section" id="produto"><div class="section-head"><span class="eyebrow">Tudo no mesmo fluxo</span><h2>Da agenda à evolução clínica, sem alternar entre cinco ferramentas.</h2><p>O sistema une operação e prática clínica com separação de acesso entre gestor, psicólogo, supervisionado, secretaria e financeiro.</p></div>
<div class="feature-grid"><?php foreach([
['01','Agenda inteligente','Visões por período, status de confirmação, atendimento on-line e fila de lembretes.'],
['02','Prontuário clínico','Anamnese, evoluções SOAP, aprovação de supervisionados e criptografia de conteúdo.'],
['03','Documentos CFP','Rascunhos de declaração, atestado, relatório, laudo e parecer com trilha de revisão.'],
['04','Financeiro','Sessões, receitas, despesas, recibos, inadimplência e base para exportações fiscais.'],
['05','Portal do paciente','Consultas, tarefas terapêuticas, diário de humor e registros entre sessões.'],
['06','Segurança por desenho','Multi-tenant, RBAC, bloqueio por inatividade, gestão de sessões e auditoria encadeada.']
] as $f):?><article class="feature-card"><div class="feature-icon"><?=$f[0]?></div><h3><?=e($f[1])?></h3><p><?=e($f[2])?></p></article><?php endforeach?></div></section>
<section class="section split" id="ia"><div><img loading="lazy" decoding="async" src="https://images.unsplash.com/photo-1758691462743-f9fc9e430d39?auto=format&fit=crop&fm=jpg&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&ixlib=rb-4.1.0&q=60&w=3000" alt="Atendimento remoto com apoio de tecnologia"></div>
<div class="ai-panel"><span class="eyebrow">Copiloto clínico</span><h2>IA assistiva, não uma caixa-preta clínica.</h2><p class="muted">Antes do envio ao provedor externo, o sistema mascara CPF, e-mail, telefone, datas e nomes conhecidos. No modo científico, a resposta recebe trechos da base interna e é instruída a não inventar referências.</p><div class="prompt-demo"><strong>Estruturar como SOAP</strong><div class="answer">Notas brutas → anonimização local → contexto científico recuperado → resposta assistiva → revisão obrigatória do psicólogo.</div></div></div></section>
<section class="section" id="seguranca"><div class="section-head"><span class="eyebrow">Privacidade e governança</span><h2>Acesso clínico não é sinônimo de acesso administrativo.</h2></div><div class="checklist">
<div class="check"><i>✓</i><div><strong>Segregação de perfis.</strong><div class="muted">Secretaria e financeiro não recebem rotas nem permissões para ler evoluções e notas clínicas.</div></div></div>
<div class="check"><i>✓</i><div><strong>Criptografia no aplicativo.</strong><div class="muted">Conteúdo de prontuários e documentos é armazenado com AES-256-GCM usando chave fora do banco.</div></div></div>
<div class="check"><i>✓</i><div><strong>Auditoria verificável.</strong><div class="muted">Eventos formam uma cadeia de hashes para facilitar detecção de adulteração.</div></div></div></div></section>
<section class="section" id="planos"><div class="section-head"><span class="eyebrow">Planos</span><h2>Comece individualmente. Evolua para equipe quando precisar.</h2></div><div class="pricing">
<?php foreach($plans as $p):$features=json_decode($p['features_json']??'[]',true)?:[];?><article class="price-card <?=($p['slug']??'')==='profissional'?'popular':''?>"><?php if(($p['slug']??'')==='profissional'):?><span class="badge">Mais completo para consultório</span><?php endif?><h3><?=e($p['name'])?></h3><div class="price">R$ <?=number_format((float)$p['price_monthly'],2,',','.')?> <small>/ mês</small></div><ul><?php foreach($features as $f):?><li><?=e($f)?></li><?php endforeach?></ul><a class="button <?=($p['slug']??'')==='profissional'?'':'secondary'?>" href="<?=e(url('cadastro.php?plan='.urlencode($p['slug'])))?>">Escolher <?=e($p['name'])?></a></article><?php endforeach?>
</div></section>
<section class="cta-band"><div><h2>Organize a clínica sem perder o foco humano.</h2><p>Crie seu espaço e configure a equipe com permissões separadas.</p></div><a class="button" href="<?=e(url('cadastro.php'))?>">Criar espaço</a></section>
<footer class="landing-footer"><div><span>© <?=date('Y')?> PsicoManager AI</span><span>Fotografias demonstrativas: Unsplash · Conteúdo clínico sempre requer revisão profissional.</span></div></footer>
<?php },['public'=>true]);