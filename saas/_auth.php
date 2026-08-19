<?php
declare(strict_types=1);
function saas_admin(): ?array{$id=(int)($_SESSION['saas_admin_id']??0);if($id<1)return null;$st=db()->prepare("SELECT id,name,email FROM saas_admins WHERE id=:id AND active=1");$st->execute(['id'=>$id]);return $st->fetch()?:null;}
function saas_require(): array{$a=saas_admin();if(!$a)redirect('saas/login.php');return $a;}
