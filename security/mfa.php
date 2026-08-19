<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require_auth();
unset($_SESSION['mfa_setup_required']);
flash('success','A autenticação em duas etapas foi removida. O acesso agora utiliza somente usuário e senha.');
redirect('security/index.php');
