<?php
declare(strict_types=1);
function portal_user(): ?array{static $cache=null;if($cache!==null)return $cache;$pid=(int)($_SESSION['portal_patient_id']??0);$tid=(int)($_SESSION['portal_tenant_id']??0);if($pid<1||$tid<1)return null;$st=db()->prepare("SELECT p.*,t.name tenant_name FROM patients p JOIN tenants t ON t.id=p.tenant_id WHERE p.id=:p AND p.tenant_id=:t AND p.status='active' AND t.status='active'");$st->execute(['p'=>$pid,'t'=>$tid]);return $cache=$st->fetch()?:null;}
function portal_require(): array{$p=portal_user();if(!$p)redirect('portal/login.php');if(!\App\Core\FeatureFlags::enabled('portal',(int)$p['tenant_id'])){http_response_code(403);exit('Portal indisponível para esta conta.');}return $p;}
